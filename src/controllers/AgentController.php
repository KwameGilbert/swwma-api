<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Models\Agent;
use App\Models\Officer;
use App\Helper\ResponseHelper;
use App\Services\AuthService;
use App\Services\UploadService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\QueryException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;
use Exception;

/**
 * AgentController
 * 
 * Handles agent management operations.
 */
class AgentController
{
    private AuthService $authService;
    private UploadService $uploadService;

    public function __construct(AuthService $authService, UploadService $uploadService)
    {
        $this->authService = $authService;
        $this->uploadService = $uploadService;
    }

    /**
     * Get all agents
     * GET /api/admin/agents
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $location = $params['location'] ?? null;
            $verified = isset($params['verified']) ? filter_var($params['verified'], FILTER_VALIDATE_BOOLEAN) : null;
            $supervisor = $params['supervisor'] ?? null;

            $query = Agent::with(['user', 'supervisor.user'])->orderBy('created_at', 'desc');

            if ($location) {
                $query->where('assigned_location', $location);
            }
            if ($verified !== null) {
                $query->where('id_verified', $verified);
            }
            if ($supervisor) {
                $query->where('supervisor_id', $supervisor);
            }

            $agents = $query->get();

            return ResponseHelper::success($response, 'Agents fetched successfully', [
                'agents' => $agents->map(fn($a) => $a->getFullProfile())->toArray()
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch agents', 500, $e->getMessage());
        }
    }

    /**
     * Get agent statistics
     * GET /api/admin/agents/stats
     */
    public function getStatistics(Request $request, Response $response): Response
    {
        try {
            $totalAgents = Agent::count();
            $activeAgents = Agent::whereHas('user', function ($q) {
                $q->where('status', 'active');
            })->count();
            
            $inactiveAgents = Agent::whereHas('user', function ($q) {
                $q->where('status', '!=', 'active');
            })->count();

            $issuesHandled = Agent::sum('reports_submitted');

            return ResponseHelper::success($response, 'Statistics fetched successfully', [
                'total_agents' => $totalAgents,
                'active_agents' => $activeAgents,
                'inactive_agents' => $inactiveAgents,
                'issues_handled' => (int) $issuesHandled
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch statistics', 500, $e->getMessage());
        }
    }

    /**
     * Get single agent
     * GET /api/admin/agents/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $agent = Agent::with(['user', 'supervisor.user'])->find($args['id']);

            if (!$agent) {
                return ResponseHelper::error($response, 'Agent not found', 404);
            }

            // Calculate issue statistics
            $stats = [
                'pending' => $agent->submittedReports()
                    ->whereIn('status', ['submitted', 'under_review'])
                    ->count(),
                'resolved' => $agent->submittedReports()
                    ->where('status', 'resolved')
                    ->count(),
                'rejected' => $agent->submittedReports()
                    ->where('status', 'rejected')
                    ->count(),
                'approved' => $agent->submittedReports()
                    ->where('status', 'approved')
                    ->count(),
            ];

            // Get recent issues
            $recentIssues = $agent->submittedReports()
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(fn($issue) => $issue->toPublicArray());

            return ResponseHelper::success($response, 'Agent fetched successfully', [
                'agent' => $agent->getFullProfile(),
                'issue_stats' => $stats,
                'recent_issues' => $recentIssues
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch agent', 500, $e->getMessage());
        }
    }

    /**
     * Create new agent
     * POST /api/admin/agents
     */
    public function store(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody() ?? [];
            $uploadedFiles = $request->getUploadedFiles();

            // Validation
            if (empty($data['name'])) {
                return ResponseHelper::error($response, 'Name is required', 400);
            }
            if (empty($data['email'])) {
                return ResponseHelper::error($response, 'Email is required', 400);
            }
            if (User::emailExists($data['email'])) {
                return ResponseHelper::error($response, 'Email already exists', 400);
            }

            // Handle profile image upload
            $profileImageUrl = $data['profile_image'] ?? null;
            $imageFile = $uploadedFiles['profile_image'] ?? null;
            if ($imageFile instanceof UploadedFileInterface && $imageFile->getError() === UPLOAD_ERR_OK) {
                try {
                    $profileImageUrl = $this->uploadService->uploadFile($imageFile, 'image', 'agents');
                } catch (Exception $e) {
                    return ResponseHelper::error($response, 'Profile image upload failed: ' . $e->getMessage(), 400);
                }
            }

            // Generate password if not provided
            $password = $data['password'] ?? $this->generatePassword();

            // Create user
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $this->authService->hashPassword($password),
                'role' => User::ROLE_AGENT,
                'status' => User::STATUS_PENDING,
                'email_verified' => false,
                'first_login' => true,
            ]);

            // Parse assigned_communities if it's a comma-separated string
            $assignedCommunities = $data['assigned_communities'] ?? null;
            if (is_string($assignedCommunities)) {
                $assignedCommunities = array_map('trim', explode(',', $assignedCommunities));
                // Remove empty strings
                $assignedCommunities = array_filter($assignedCommunities);
            }

            $providedAgentCode = isset($data['agent_code']) ? trim((string) $data['agent_code']) : null;
            if (!empty($providedAgentCode) && Agent::where('agent_code', $providedAgentCode)->exists()) {
                return ResponseHelper::error($response, 'Agent code already exists', 400);
            }

            $supervisorId = $data['supervisor_id'] ?? null;
            if (!$supervisorId) {
                $authUser = $request->getAttribute('user');
                if ($authUser) {
                    $authUserObj = is_array($authUser) ? (object)$authUser : $authUser;
                    $authUserId = $authUserObj->id ?? null;
                    if ($authUserId) {
                        $dbUser = User::find($authUserId);
                        if ($dbUser && $dbUser->role === User::ROLE_OFFICER) {
                            $officer = Officer::findByUserId($dbUser->id);
                            if ($officer) {
                                $supervisorId = $officer->id;
                            }
                        }
                    }
                }
            }

            $agentPayload = [
                'user_id' => $user->id,
                'supervisor_id' => $supervisorId,
                'assigned_communities' => !empty($assignedCommunities) ? $assignedCommunities : null,
                'assigned_location' => $data['assigned_location'] ?? null,
                'can_submit_reports' => isset($data['can_submit_reports']) ? filter_var($data['can_submit_reports'], FILTER_VALIDATE_BOOLEAN) : true,
                'can_collect_data' => isset($data['can_collect_data']) ? filter_var($data['can_collect_data'], FILTER_VALIDATE_BOOLEAN) : true,
                'can_register_residents' => isset($data['can_register_residents']) ? filter_var($data['can_register_residents'], FILTER_VALIDATE_BOOLEAN) : false,
                'profile_image' => $profileImageUrl,
                'id_type' => $data['id_type'] ?? null,
                'id_number' => $data['id_number'] ?? null,
                'id_verified' => false,
                'address' => $data['address'] ?? null,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
            ];

            $agent = null;
            $lastAgentCreateError = null;
            $maxAttempts = !empty($providedAgentCode) ? 1 : 5;

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                $candidateAgentCode = !empty($providedAgentCode) ? $providedAgentCode : Agent::generateAgentCode();

                try {
                    $agent = Agent::create(array_merge($agentPayload, [
                        'agent_code' => $candidateAgentCode,
                    ]));
                    $lastAgentCreateError = null;
                    break;
                } catch (QueryException $queryException) {
                    if (empty($providedAgentCode) && $this->isDuplicateAgentCodeException($queryException)) {
                        $lastAgentCreateError = $queryException;
                        continue;
                    }
                    throw $queryException;
                }
            }

            if (!$agent) {
                $user->delete();
                if ($lastAgentCreateError) {
                    throw $lastAgentCreateError;
                }
                throw new Exception('Failed to allocate unique agent code');
            }

            return ResponseHelper::success($response, 'Agent created successfully', [
                'agent' => $agent->getFullProfile(),
                'generated_password' => isset($data['password']) ? null : $password,
            ], 201);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to create agent', 500, $e->getMessage());
        }
    }

    /**
     * Update agent
     * PUT /api/admin/agents/{id}
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $agent = Agent::with('user')->find($args['id']);

            if (!$agent) {
                return ResponseHelper::error($response, 'Agent not found', 404);
            }

            $data = $request->getParsedBody() ?? [];
            $uploadedFiles = $request->getUploadedFiles();

            // Handle profile image upload
            $profileImageUrl = $data['profile_image'] ?? $agent->profile_image;
            $imageFile = $uploadedFiles['profile_image'] ?? null;
            if ($imageFile instanceof UploadedFileInterface && $imageFile->getError() === UPLOAD_ERR_OK) {
                try {
                    $profileImageUrl = $this->uploadService->replaceFile($imageFile, $agent->profile_image, 'image', 'agents');
                } catch (Exception $e) {
                    return ResponseHelper::error($response, 'Profile image upload failed: ' . $e->getMessage(), 400);
                }
            }

            // Update user data
            if ($agent->user) {
                $userData = [];
                if (isset($data['name'])) $userData['name'] = $data['name'];
                if (isset($data['phone'])) $userData['phone'] = $data['phone'];
                if (isset($data['status'])) $userData['status'] = $data['status'];
                if (!empty($data['password'])) {
                    $userData['password'] = $this->authService->hashPassword($data['password']);
                }
                if (isset($data['email']) && $data['email'] !== $agent->user->email) {
                    if (User::emailExists($data['email'], $agent->user->id)) {
                        return ResponseHelper::error($response, 'Email is already in use by another account', 400);
                    }
                    $userData['email'] = $data['email'];
                }
                
                if (!empty($userData)) {
                    $agent->user->update($userData);
                }
            }

            // Parse assigned_communities if provided
            $assignedCommunities = $agent->assigned_communities;
            if (isset($data['assigned_communities'])) {
                $rawCommunities = $data['assigned_communities'];
                if (is_string($rawCommunities)) {
                    $parsed = array_map('trim', explode(',', $rawCommunities));
                    $assignedCommunities = array_filter($parsed);
                } else {
                    $assignedCommunities = $rawCommunities;
                }
            }

            // Update agent profile
            $agent->update([
                'supervisor_id' => $data['supervisor_id'] ?? $agent->supervisor_id,
                'assigned_communities' => $assignedCommunities,
                'assigned_location' => $data['assigned_location'] ?? $agent->assigned_location,
                'can_submit_reports' => isset($data['can_submit_reports']) ? filter_var($data['can_submit_reports'], FILTER_VALIDATE_BOOLEAN) : $agent->can_submit_reports,
                'can_collect_data' => isset($data['can_collect_data']) ? filter_var($data['can_collect_data'], FILTER_VALIDATE_BOOLEAN) : $agent->can_collect_data,
                'can_register_residents' => isset($data['can_register_residents']) ? filter_var($data['can_register_residents'], FILTER_VALIDATE_BOOLEAN) : $agent->can_register_residents,
                'profile_image' => $profileImageUrl,
                'id_type' => $data['id_type'] ?? $agent->id_type,
                'id_number' => $data['id_number'] ?? $agent->id_number,
                'address' => $data['address'] ?? $agent->address,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? $agent->emergency_contact_name,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? $agent->emergency_contact_phone,
            ]);

            return ResponseHelper::success($response, 'Agent updated successfully', [
                'agent' => $agent->fresh()->getFullProfile()
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to update agent', 500, $e->getMessage());
        }
    }

    /**
     * Delete agent
     * DELETE /api/admin/agents/{id}
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        try {
            $agent = Agent::with('user')->find($args['id']);

            if (!$agent) {
                return ResponseHelper::error($response, 'Agent not found', 404);
            }

            // Delete user (will cascade to agent profile)
            if ($agent->user) {
                $agent->user->delete();
            }

            return ResponseHelper::success($response, 'Agent deleted successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to delete agent', 500, $e->getMessage());
        }
    }

    /**
     * Verify agent ID
     * POST /api/admin/agents/{id}/verify
     */
    public function verify(Request $request, Response $response, array $args): Response
    {
        try {
            $agent = Agent::with('user')->find($args['id']);

            if (!$agent) {
                return ResponseHelper::error($response, 'Agent not found', 404);
            }

            $agent->markAsVerified();

            // Activate user account
            if ($agent->user) {
                $agent->user->update(['status' => User::STATUS_ACTIVE]);
            }

            return ResponseHelper::success($response, 'Agent verified successfully', [
                'agent' => $agent->fresh()->getFullProfile()
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to verify agent', 500, $e->getMessage());
        }
    }

    /**
     * Get agent's submitted reports (for agent dashboard)
     * GET /api/agent/my-reports
     */
    public function myReports(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $agent = Agent::findByUserId($user->id);

            if (!$agent) {
                return ResponseHelper::error($response, 'Agent profile not found', 404);
            }

            $reports = $agent->submittedReports()
                ->with(['sector', 'subSector', 'mainCommunity', 'smallerCommunity', 'suburb', 'cottage'])
                ->orderBy('created_at', 'desc')
                ->get();

            return ResponseHelper::success($response, 'Reports fetched successfully', [
                'reports' => $reports->map(fn($r) => $r->toPublicArray())->toArray(),
                'total_submitted' => $agent->reports_submitted,
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch reports', 500, $e->getMessage());
        }
    }

    /**
     * Get agent profile (for agent dashboard)
     * GET /api/agent/profile
     */
    public function profile(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $agent = Agent::with(['user', 'supervisor.user'])->where('user_id', $user->id)->first();

            if (!$agent) {
                return ResponseHelper::error($response, 'Agent profile not found', 404);
            }

            return ResponseHelper::success($response, 'Profile fetched successfully', [
                'agent' => $agent->getFullProfile()
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch profile', 500, $e->getMessage());
        }
    }

    /**
     * Submit a new issue report (for agents)
     * POST /api/agent/issues
     */
    public function submitIssue(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $agent = Agent::findByUserId($user->id);

            if (!$agent) {
                return ResponseHelper::error($response, 'Agent profile not found', 404);
            }

            if (!$agent->can_submit_reports) {
                return ResponseHelper::error($response, 'You do not have permission to submit reports', 403);
            }

            $body = $request->getParsedBody();
            
            // Validate required fields
            $required = ['title', 'description', 'category', 'priority', 'location'];
            foreach ($required as $field) {
                if (empty($body[$field])) {
                    return ResponseHelper::error($response, "Field '{$field}' is required", 400);
                }
            }

            // Resolve sector and sub-sector IDs from names
            $sectorId = null;
            $subSectorId = null;
            if (!empty($body['sector'])) {
                $sector = \App\Models\Sector::where('name', $body['sector'])->first();
                $sectorId = $sector ? $sector->id : null;

                if ($sectorId && !empty($body['subsector'])) {
                    $subSector = \App\Models\SubSector::where('name', $body['subsector'])
                        ->where('sector_id', $sectorId)
                        ->first();
                    $subSectorId = $subSector ? $subSector->id : null;
                }
            }

            // Resolve location hierarchy IDs from names
            $mainCommunityId = null;
            $smallerCommunityId = null;
            $suburbId = null;

            if (!empty($body['location'])) {
                $mainCommunity = \App\Models\Location::where('name', $body['location'])
                    ->where('type', 'community')
                    ->first();
                $mainCommunityId = $mainCommunity ? $mainCommunity->id : null;

                if ($mainCommunityId) {
                    if (!empty($body['smaller_community'])) {
                        $smallerCommunity = \App\Models\Location::where('name', $body['smaller_community'])
                            ->where('parent_id', $mainCommunityId)
                            ->where('type', 'smaller_community')
                            ->first();
                        $smallerCommunityId = $smallerCommunity ? $smallerCommunity->id : null;
                    }

                    if (!empty($body['suburb'])) {
                        $suburb = \App\Models\Location::where('name', $body['suburb'])
                            ->where('parent_id', $mainCommunityId)
                            ->where('type', 'suburb')
                            ->first();
                        $suburbId = $suburb ? $suburb->id : null;
                    }
                }
            }

            // Build payload with modern + legacy keys, then keep only columns that exist.
            $payload = [
                'title' => $body['title'],
                'description' => $body['description'],
                'category' => $body['category'],
                'issue_type' => $body['issue_type'] ?? ($body['type'] ?? 'community_based'),
                'type' => $body['type'] ?? ($body['issue_type'] ?? 'community_based'),
                'priority' => $body['priority'],
                'location' => $body['location'],
                'main_community_id' => $mainCommunityId,
                'smaller_community_id' => $smallerCommunityId,
                'smaller_community' => $body['smaller_community'] ?? null,
                'suburb_id' => $suburbId,
                'suburb' => $body['suburb'] ?? null,
                'cottage_id' => null,
                'cottage' => $body['cottage'] ?? null,
                'latitude' => $body['latitude'] ?? null,
                'longitude' => $body['longitude'] ?? null,
                'sector_id' => $sectorId,
                'sector' => $body['sector'] ?? null,
                'sub_sector_id' => $subSectorId,
                'subsector' => $body['subsector'] ?? null,
                'affected_people_count' => $body['people_affected'] ?? null,
                'people_affected' => $body['people_affected'] ?? null,
                'additional_notes' => $body['additional_notes'] ?? null,
                'reporter_name' => $body['reporter_name'] ?? null,
                'reporter_phone' => $body['reporter_phone'] ?? null,
                'reporter_email' => $body['reporter_email'] ?? null,
                'reporter_gender' => $body['reporter_gender'] ?? null,
                'reporter_address' => $body['reporter_address'] ?? null,
                'constituent_name' => $body['reporter_name'] ?? null,
                'constituent_contact' => $body['reporter_phone'] ?? null,
                'constituent_email' => $body['reporter_email'] ?? null,
                'constituent_gender' => $body['reporter_gender'] ?? null,
                'constituent_address' => $body['reporter_address'] ?? null,
                'submitted_by_agent_id' => $agent->id,
                'status' => 'submitted',
                'case_id' => 'ISS-' . strtoupper(uniqid()),
            ];

            $issue = new \App\Models\IssueReport($this->filterIssueReportPayload($payload));

            $issue->save();

            // Increment agent's report count
            $agent->increment('reports_submitted');

            return ResponseHelper::success($response, 'Issue submitted successfully', [
                'issue' => $issue->toPublicArray(),
            ], 201);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to submit issue', 500, $e->getMessage());
        }
    }

    /**
     * Get a single issue by ID (for agents to view their own issues)
     * GET /api/agent/issues/{id}
     */
    public function getIssue(Request $request, Response $response, array $args): Response
    {
        try {
            $user = $request->getAttribute('user');
            $agent = Agent::findByUserId($user->id);

            if (!$agent) {
                return ResponseHelper::error($response, 'Agent profile not found', 404);
            }

            $issueId = (int) $args['id'];
            $issue = \App\Models\IssueReport::with([
                    'assignedOfficer.user',
                    'assignedTaskForce',
                    'sector',
                    'subSector',
                    'mainCommunity',
                    'smallerCommunity',
                    'suburb',
                    'cottage',
                ])
                ->where('id', $issueId)
                ->where('submitted_by_agent_id', $agent->id)
                ->first();

            if (!$issue) {
                return ResponseHelper::error($response, 'Issue not found or access denied', 404);
            }

            $issueData = $issue->toFullArray();

            return ResponseHelper::success($response, 'Issue fetched successfully', [
                'issue' => $issueData,
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch issue', 500, $e->getMessage());
        }
    }

    /**
     * Update agent profile
     * PUT /api/agent/profile
     */
    public function updateProfile(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $agent = Agent::with('user')->where('user_id', $user->id)->first();

            if (!$agent) {
                return ResponseHelper::error($response, 'Agent profile not found', 404);
            }

            $body = $request->getParsedBody();

            // Update user fields
            if (!empty($body['name'])) {
                $agent->user->name = $body['name'];
            }
            if (!empty($body['email'])) {
                // Check if email is already taken
                $existingUser = User::where('email', $body['email'])
                    ->where('id', '!=', $user->id)
                    ->first();
                if ($existingUser) {
                    return ResponseHelper::error($response, 'Email is already in use', 400);
                }
                $agent->user->email = $body['email'];
            }
            if (!empty($body['phone'])) {
                $agent->user->phone = $body['phone'];
            }
            
            $agent->user->save();

            // Update agent-specific fields
            if (!empty($body['address'])) {
                $agent->address = $body['address'];
            }

            $agent->save();

            return ResponseHelper::success($response, 'Profile updated successfully', [
                'agent' => $agent->fresh()->getFullProfile()
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to update profile', 500, $e->getMessage());
        }
    }

    /**
     * Change agent password
     * PUT /api/agent/password
     */
    public function changePassword(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $body = $request->getParsedBody();

            // Validate required fields
            if (empty($body['current_password']) || empty($body['new_password'])) {
                return ResponseHelper::error($response, 'Current password and new password are required', 400);
            }

            if (strlen($body['new_password']) < 8) {
                return ResponseHelper::error($response, 'New password must be at least 8 characters', 400);
            }

            // Verify current password
            $dbUser = User::find($user->id);
            if (!$dbUser || !password_verify($body['current_password'], $dbUser->password)) {
                return ResponseHelper::error($response, 'Current password is incorrect', 400);
            }

            // Update password
            $dbUser->password = password_hash($body['new_password'], PASSWORD_DEFAULT);
            $dbUser->save();

            return ResponseHelper::success($response, 'Password changed successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to change password', 500, $e->getMessage());
        }
    }

    /**
     * Check if query exception is a duplicate agent_code constraint error.
     */
    private function isDuplicateAgentCodeException(QueryException $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, 'SQLSTATE[23000]')
            && str_contains($message, 'Duplicate entry')
            && str_contains($message, 'agent_code');
    }

    /**
     * Generate random password
     */
    private function generatePassword(int $length = 12): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        return substr(str_shuffle($chars), 0, $length);
    }

    /**
     * Keep only columns that exist in the current DB schema.
     * This supports both legacy and migrated issue_reports tables.
     */
    /**
     * Get agent's dashboard stats
     * GET /v1/agent/stats
     */
    public function getStats(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $agent = Agent::findByUserId($user->id);

            if (!$agent) {
                return ResponseHelper::error($response, 'Agent profile not found', 404);
            }

            // Fetch count of submitted reports grouped by status
            $reportsQuery = $agent->submittedReports();
            $reports = $reportsQuery->get(['status']);

            $total = count($reports);
            $pending = 0;
            $approved = 0;
            $inProgress = 0;
            $resolved = 0;
            $rejected = 0;

            foreach ($reports as $report) {
                $status = strtolower($report->status ?? '');
                
                if (
                    $status === 'submitted' ||
                    $status === 'pending' ||
                    $status === 'under_officer_review'
                ) {
                    $pending++;
                } elseif (
                    $status === 'forwarded_to_admin' ||
                    $status === 'approved' ||
                    $status === 'assigned_to_task_force'
                ) {
                    $approved++;
                } elseif (
                    $status === 'pending_assessment' ||
                    $status === 'assessment_in_progress' ||
                    $status === 'assessment_submitted' ||
                    $status === 'resources_allocated' ||
                    $status === 'resolution_in_progress' ||
                    $status === 'resolution_submitted' ||
                    $status === 'in_progress'
                ) {
                    $inProgress++;
                } elseif ($status === 'resolved' || $status === 'closed') {
                    $resolved++;
                } elseif ($status === 'rejected') {
                    $rejected++;
                }
            }

            $stats = [
                'total' => $total,
                'pending' => $pending,
                'approved' => $approved,
                'inProgress' => $inProgress,
                'resolved' => $resolved,
                'rejected' => $rejected,
            ];

            return ResponseHelper::success($response, 'Agent statistics fetched successfully', $stats);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch agent statistics', 500, $e->getMessage());
        }
    }

    private function filterIssueReportPayload(array $payload): array
    {
        static $columns = null;

        if ($columns === null) {
            try {
                $columns = Capsule::schema()->getColumnListing('issue_reports');
            } catch (\Throwable $e) {
                // Fallback: keep payload unchanged if schema inspection fails.
                return $payload;
            }
        }

        $allowed = array_flip($columns);
        return array_filter(
            $payload,
            static fn($key) => isset($allowed[$key]),
            ARRAY_FILTER_USE_KEY,
        );
    }
}

