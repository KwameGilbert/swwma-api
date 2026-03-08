<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Issue;
use App\Models\User;
use App\Helper\ResponseHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use Psr\Http\Message\UploadedFileInterface;
use Respect\Validation\Validator as v;

/**
 * AgentDashboardController
 * Handles statistics and data specific to the Agent Dashboard.
 */
class AgentDashboardController
{
    /**
     * Get statistics for the authenticated agent
     * GET /v1/agent/stats
     */
    public function getStats(Request $request, Response $response): Response
    {
        try {
            $agent = $request->getAttribute('user');
            
            $issueStats = [
                'total' => Issue::where('agent_id', $agent->id)->count(),
                'pending' => Issue::where('agent_id', $agent->id)
                    ->whereIn('status', [Issue::STATUS_SUBMITTED, 'under_review'])
                    ->count(),
                'approved' => Issue::where('agent_id', $agent->id)
                    ->whereIn('status', ['approved', 'forwarded_to_admin'])
                    ->count(),
                'inProgress' => Issue::where('agent_id', $agent->id)
                    ->whereIn('status', [
                        Issue::STATUS_ASSESSMENT_IN_PROGRESS, 
                        Issue::STATUS_RESOLUTION_IN_PROGRESS,
                        'resources_allocated'
                    ])
                    ->count(),
                'resolved' => Issue::where('agent_id', $agent->id)->where('status', Issue::STATUS_RESOLVED)->count(),
                'rejected' => Issue::where('agent_id', $agent->id)->where('status', 'rejected')->count(),
            ];

            return ResponseHelper::success($response, 'Agent statistics fetched', $issueStats);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch agent statistics', 500, $e->getMessage());
        }
    }

    /**
     * Get reports (issues) created by the authenticated agent
     * GET /v1/agent/my-reports
     */
    public function getMyReports(Request $request, Response $response): Response
    {
        try {
            $agent = $request->getAttribute('user');
            
            $issues = Issue::with(['category', 'community', 'suburb', 'constituent'])
                ->where('agent_id', $agent->id)
                ->latest()
                ->get();

            $reports = $issues->map(function ($issue) {
                return [
                    'id' => $issue->id,
                    'case_id' => 'ISS-' . str_pad((string)$issue->id, 5, '0', STR_PAD_LEFT),
                    'title' => $issue->title,
                    'description' => $issue->description,
                    'category' => $issue->category_name,
                    'community' => $issue->community->name ?? 'Unknown',
                    'suburb' => $issue->suburb->name ?? null,
                    'specific_location' => $issue->specific_location,
                    'status' => $issue->status,
                    'priority' => $issue->priority,
                    'issue_type' => $issue->issue_type,
                    'images' => $issue->images ?? [],
                    'reporter_name' => $issue->constituent->name ?? null,
                    'reporter_phone' => $issue->constituent->phone_number ?? null,
                    'created_at' => $issue->created_at->toIso8601String(),
                    'updated_at' => $issue->updated_at->toIso8601String(),
                ];
            });

            return ResponseHelper::success($response, 'Agent reports fetched', [
                'reports' => $reports,
                'total_submitted' => $reports->count()
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch agent reports', 500, $e->getMessage());
        }
    }

    /**
     * Get details for a specific issue (mapped for agent dashboard)
     * GET /v1/agent/issues/{id}
     */
    public function getIssueDetail(Request $request, Response $response, array $args): Response
    {
        try {
            $agent = $request->getAttribute('user');
            $issue = Issue::with(['category', 'sector', 'subsector', 'community', 'suburb', 'constituent', 'officer.officerProfile'])
                ->where('id', $args['id'])
                ->where('agent_id', $agent->id)
                ->first();

            if (!$issue) {
                return ResponseHelper::error($response, 'Issue not found or unauthorized', 404);
            }

            $mappedIssue = [
                'id' => $issue->id,
                'case_id' => 'ISS-' . str_pad((string)$issue->id, 5, '0', STR_PAD_LEFT),
                'title' => $issue->title,
                'description' => $issue->description,
                'category' => $issue->category_name,
                'issue_type' => $issue->issue_type,
                'priority' => $issue->priority,
                'status' => $issue->status,
                'community' => $issue->community->name ?? 'Unknown',
                'suburb' => $issue->suburb->name ?? null,
                'specific_location' => $issue->specific_location,
                'sector' => $issue->sector->name ?? null,
                'subsector' => $issue->subsector->name ?? null,
                'people_affected' => $issue->people_affected,
                'estimated_budget' => $issue->estimated_budget,
                'additional_notes' => $issue->details, // Also mapping details here just in case
                'reporter_name' => $issue->constituent->name ?? null,
                'reporter_phone' => $issue->constituent->phone_number ?? null,
                'reporter_email' => $issue->constituent->email ?? null,
                'reporter_gender' => $issue->constituent->gender ?? null,
                'reporter_address' => $issue->constituent->home_address ?? null,
                'images' => $issue->images ?? [],
                'assigned_officer' => $issue->officer ? [
                    'id' => $issue->officer->id,
                    'user' => [
                        'name' => $issue->officer->getFullName(),
                        'email' => $issue->officer->email
                    ]
                ] : null,
                'created_at' => $issue->created_at->toIso8601String(),
                'updated_at' => $issue->updated_at ? $issue->updated_at->toIso8601String() : null,
            ];

            return ResponseHelper::success($response, 'Issue detail fetched', ['issue' => $mappedIssue]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch issue details', 500, $e->getMessage());
        }
    }

    /**
     * Get the authenticated agent's profile
     * GET /v1/agent/profile
     */
    public function getProfile(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $userModel = User::with('agentProfile')->find($user->id);

            if (!$userModel) {
                return ResponseHelper::error($response, 'User not found', 404);
            }

            $profile = $userModel->agentProfile;
            
            // Map to standardized format
            $mappedAgent = $profile->toArray();
            $mappedAgent['user'] = [
                'id' => $userModel->id,
                'name' => $userModel->getFullName(),
                'email' => $userModel->email,
                'phone' => $userModel->phone,
                'role' => $userModel->role,
                'status' => $userModel->status,
            ];

            $mappedAgent['reports_submitted'] = Issue::where('agent_id', $userModel->id)->count();
            $mappedAgent['last_active_at'] = $userModel->updated_at->toIso8601String();

            return ResponseHelper::success($response, 'Profile fetched successfully', ['agent' => $mappedAgent]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch profile', 500, $e->getMessage());
        }
    }

    /**
     * Update the authenticated agent's profile
     * PUT /v1/agent/profile
     */
    public function updateProfile(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $userModel = User::find($user->id);
            $data = $request->getParsedBody();

            // 1. Update User Record
            if (isset($data['email']) && $data['email'] !== $userModel->email) {
                if (User::where('email', $data['email'])->where('id', '!=', $userModel->id)->exists()) {
                    return ResponseHelper::error($response, 'Email already in use', 409);
                }
                $userModel->email = $data['email'];
            }
            
            if (isset($data['phone'])) $userModel->phone = $data['phone'];
            $userModel->save();

            // 2. Update Agent Profile
            $profile = \App\Models\AgentProfile::where('user_id', $userModel->id)->first();
            if ($profile) {
                if (isset($data['name'])) {
                    $parts = explode(' ', $data['name'], 2);
                    $profile->first_name = $parts[0] ?? '';
                    $profile->last_name = $parts[1] ?? '';
                }
                if (isset($data['first_name'])) $profile->first_name = $data['first_name'];
                if (isset($data['last_name'])) $profile->last_name = $data['last_name'];
                if (isset($data['address'])) $profile->address = $data['address'];
                if (isset($data['gender'])) $profile->gender = $data['gender'];
                $profile->save();
            }

            // Return standardized format
            $mappedAgent = $profile->toArray();
            $mappedAgent['user'] = [
                'id' => $userModel->id,
                'name' => $userModel->getFullName(),
                'email' => $userModel->email,
                'phone' => $userModel->phone,
                'role' => $userModel->role,
                'status' => $userModel->status,
            ];

            $mappedAgent['reports_submitted'] = Issue::where('agent_id', $userModel->id)->count();
            $mappedAgent['last_active_at'] = $userModel->updated_at->toIso8601String();

            return ResponseHelper::success($response, 'Profile updated successfully', ['agent' => $mappedAgent]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to update profile', 500, $e->getMessage());
        }
    }

    /**
     * Upload profile image
     * POST /v1/agent/profile/image
     */
    public function uploadProfileImage(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $uploadedFiles = $request->getUploadedFiles();

            if (empty($uploadedFiles['profile_image'])) {
                return ResponseHelper::error($response, 'No image uploaded', 400);
            }

            /** @var UploadedFileInterface $uploadedFile */
            $uploadedFile = $uploadedFiles['profile_image'];
            if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                return ResponseHelper::error($response, 'Upload failed', 400);
            }

            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($uploadedFile->getClientMediaType(), $allowedTypes)) {
                return ResponseHelper::error($response, 'Invalid file type. Only JPG, PNG, GIF and WEBP are allowed.', 400);
            }

            // Create directory if not exists
            $uploadDir = BASE . 'public/uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate filename
            $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
            $filename = sprintf('agent_%d_%s.%s', $user->id, bin2hex(random_bytes(8)), $extension ?: 'jpg');
            
            $uploadedFile->moveTo($uploadDir . $filename);
            
            // Get base URL for images
            $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost:8080';
            $imageUrl = $baseUrl . '/uploads/profiles/' . $filename;

            // Update profile
            $profile = \App\Models\AgentProfile::where('user_id', $user->id)->first();
            if ($profile) {
                // Delete old image if exists and is local
                if ($profile->profile_image && strpos($profile->profile_image, $baseUrl) !== false) {
                    $oldFilename = basename($profile->profile_image);
                    $oldPath = $uploadDir . $oldFilename;
                    if (file_exists($oldPath) && is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                
                $profile->profile_image = $imageUrl;
                $profile->save();
            }

            // Return standardized format
            $userModel = User::find($user->id);
            $mappedAgent = $profile->toArray();
            $mappedAgent['user'] = [
                'id' => $userModel->id,
                'name' => $userModel->getFullName(),
                'email' => $userModel->email,
                'phone' => $userModel->phone,
                'role' => $userModel->role,
                'status' => $userModel->status,
            ];
            $mappedAgent['reports_submitted'] = Issue::where('agent_id', $userModel->id)->count();
            $mappedAgent['last_active_at'] = $userModel->updated_at->toIso8601String();

            return ResponseHelper::success($response, 'Profile image updated successfully', ['agent' => $mappedAgent]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to upload profile image', 500, $e->getMessage());
        }
    }
}
