<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Models\Officer;
use App\Helper\ResponseHelper;
use App\Services\AuthService;
use App\Services\UploadService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;
use Exception;

/**
 * OfficerController
 * 
 * Handles officer management operations.
 */
class OfficerController
{
    private AuthService $authService;
    private UploadService $uploadService;

    public function __construct(AuthService $authService, UploadService $uploadService)
    {
        $this->authService = $authService;
        $this->uploadService = $uploadService;
    }

    /**
     * Get all officers
     * GET /api/admin/officers
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $department = $params['department'] ?? null;

            $query = Officer::with('user')->orderBy('created_at', 'desc');

            if ($department) {
                $query->where('department', $department);
            }

            $officers = $query->get();

            return ResponseHelper::success($response, 'Officers fetched successfully', [
                'officers' => $officers->map(fn($o) => $o->getFullProfile())->toArray()
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch officers', 500, $e->getMessage());
        }
    }

    /**
     * Get single officer
     * GET /api/admin/officers/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $officer = Officer::with(['user', 'supervisedAgents.user'])->find($args['id']);

            if (!$officer) {
                return ResponseHelper::error($response, 'Officer not found', 404);
            }

            return ResponseHelper::success($response, 'Officer fetched successfully', [
                'officer' => $officer->getFullProfile()
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch officer', 500, $e->getMessage());
        }
    }

    /**
     * Create new officer
     * POST /api/admin/officers
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
                    $profileImageUrl = $this->uploadService->uploadFile($imageFile, 'image', 'officers');
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
                'role' => User::ROLE_OFFICER,
                'status' => User::STATUS_ACTIVE,
                'email_verified' => true,
                'first_login' => true,
            ]);

            // Create officer profile
            $officer = Officer::create([
                'user_id' => $user->id,
                'employee_id' => $data['employee_id'] ?? Officer::generateEmployeeId(),
                'title' => $data['title'] ?? null,
                'department' => $data['department'] ?? null,
                'assigned_sectors' => $data['assigned_sectors'] ?? null,
                'assigned_locations' => $data['assigned_locations'] ?? null,
                'can_manage_projects' => $data['can_manage_projects'] ?? true,
                'can_manage_reports' => $data['can_manage_reports'] ?? true,
                'can_manage_events' => $data['can_manage_events'] ?? false,
                'can_publish_content' => $data['can_publish_content'] ?? false,
                'profile_image' => $profileImageUrl,
                'bio' => $data['bio'] ?? null,
                'office_location' => $data['office_location'] ?? null,
                'office_phone' => $data['office_phone'] ?? null,
            ]);

            return ResponseHelper::success($response, 'Officer created successfully', [
                'officer' => $officer->getFullProfile(),
                'generated_password' => isset($data['password']) ? null : $password,
            ], 201);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to create officer', 500, $e->getMessage());
        }
    }

    /**
     * Update officer
     * PUT /api/admin/officers/{id}
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $officer = Officer::with('user')->find($args['id']);

            if (!$officer) {
                return ResponseHelper::error($response, 'Officer not found', 404);
            }

            $data = $request->getParsedBody() ?? [];
            $uploadedFiles = $request->getUploadedFiles();

            // Handle profile image upload
            $profileImageUrl = $data['profile_image'] ?? $officer->profile_image;
            $imageFile = $uploadedFiles['profile_image'] ?? null;
            if ($imageFile instanceof UploadedFileInterface && $imageFile->getError() === UPLOAD_ERR_OK) {
                try {
                    $profileImageUrl = $this->uploadService->replaceFile($imageFile, $officer->profile_image, 'image', 'officers');
                } catch (Exception $e) {
                    return ResponseHelper::error($response, 'Profile image upload failed: ' . $e->getMessage(), 400);
                }
            }

            // Update user data
            if ($officer->user) {
                $userData = [];
                if (isset($data['name'])) $userData['name'] = $data['name'];
                if (isset($data['phone'])) $userData['phone'] = $data['phone'];
                if (isset($data['status'])) $userData['status'] = $data['status'];
                
                if (!empty($userData)) {
                    $officer->user->update($userData);
                }
            }

            // Update officer profile
            $officer->update([
                'title' => $data['title'] ?? $officer->title,
                'department' => $data['department'] ?? $officer->department,
                'assigned_sectors' => $data['assigned_sectors'] ?? $officer->assigned_sectors,
                'assigned_locations' => $data['assigned_locations'] ?? $officer->assigned_locations,
                'can_manage_projects' => $data['can_manage_projects'] ?? $officer->can_manage_projects,
                'can_manage_reports' => $data['can_manage_reports'] ?? $officer->can_manage_reports,
                'can_manage_events' => $data['can_manage_events'] ?? $officer->can_manage_events,
                'can_publish_content' => $data['can_publish_content'] ?? $officer->can_publish_content,
                'profile_image' => $profileImageUrl,
                'bio' => $data['bio'] ?? $officer->bio,
                'office_location' => $data['office_location'] ?? $officer->office_location,
                'office_phone' => $data['office_phone'] ?? $officer->office_phone,
            ]);

            return ResponseHelper::success($response, 'Officer updated successfully', [
                'officer' => $officer->fresh()->getFullProfile()
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to update officer', 500, $e->getMessage());
        }
    }

    /**
     * Delete officer
     * DELETE /api/admin/officers/{id}
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        try {
            $officer = Officer::with('user')->find($args['id']);

            if (!$officer) {
                return ResponseHelper::error($response, 'Officer not found', 404);
            }

            // Check if officer has assigned agents
            if ($officer->getSupervisedAgentsCount() > 0) {
                return ResponseHelper::error($response, 'Cannot delete officer with assigned agents', 400);
            }

            // Delete user (will cascade to officer profile)
            if ($officer->user) {
                $officer->user->delete();
            }

            return ResponseHelper::success($response, 'Officer deleted successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to delete officer', 500, $e->getMessage());
        }
    }

    /**
     * Get officer's assigned reports
     * GET /api/officer/reports
     */
    public function myReports(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $officer = Officer::findByUserId($user->id);

            if (!$officer) {
                return ResponseHelper::error($response, 'Officer profile not found', 404);
            }

            $reports = $officer->assignedReports()
                ->with(['submittedByAgent.user'])
                ->orderBy('created_at', 'desc')
                ->get();

            return ResponseHelper::success($response, 'Reports fetched successfully', [
                'reports' => $reports->toArray()
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch reports', 500, $e->getMessage());
        }
    }

    /**
     * Get officer's supervised agents
     * GET /api/officer/agents
     */
    public function myAgents(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $officer = Officer::findByUserId($user->id);

            if (!$officer) {
                return ResponseHelper::error($response, 'Officer profile not found', 404);
            }

            $agents = $officer->supervisedAgents()->with('user')->get();

            return ResponseHelper::success($response, 'Agents fetched successfully', [
                'agents' => $agents->map(fn($a) => [
                    'id' => $a->id,
                    'name' => $a->user?->name,
                    'email' => $a->user?->email,
                    'phone' => $a->user?->phone,
                    'agent_code' => $a->agent_code,
                    'assigned_location' => $a->assigned_location,
                ])->toArray()
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch agents', 500, $e->getMessage());
        }
    }

    /**
     * Get all agents for officer management
     * GET /v1/officer/management/agents
     */
    public function getManagementAgents(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $officer = Officer::findByUserId($user->id);

            if (!$officer) {
                return ResponseHelper::error($response, 'Officer profile not found', 404);
            }

            $params = $request->getQueryParams();
            $status = $params['status'] ?? null;

            $query = $officer->supervisedAgents()->with('user');

            if ($status) {
                $query->whereHas('user', function($q) use ($status) {
                    $q->where('status', $status);
                });
            }

            $agents = $query->get();

            return ResponseHelper::success($response, 'Agents fetched successfully', [
                'agents' => $agents->map(fn($a) => $a->getFullProfile())->toArray()
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch management agents', 500, $e->getMessage());
        }
    }

    /**
     * Get agent statistics for officer
     * GET /v1/officer/management/agents/stats
     */
    public function getAgentStats(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $officer = Officer::findByUserId($user->id);

            if (!$officer) {
                return ResponseHelper::error($response, 'Officer profile not found', 404);
            }

            $supervisedAgentIds = $officer->supervisedAgents()->pluck('id')->toArray();

            if (empty($supervisedAgentIds)) {
                return ResponseHelper::success($response, 'Statistics fetched successfully', [
                    'total_agents' => 0,
                    'active_agents' => 0,
                    'inactive_agents' => 0,
                    'issues_handled' => 0
                ]);
            }

            $totalAgents = count($supervisedAgentIds);

            $activeAgents = \App\Models\Agent::whereIn('id', $supervisedAgentIds)
                ->whereHas('user', function ($q) {
                    $q->where('status', 'active');
                })->count();

            $inactiveAgents = $totalAgents - $activeAgents;

            $issuesHandled = \App\Models\Agent::whereIn('id', $supervisedAgentIds)
                ->sum('reports_submitted');

            return ResponseHelper::success($response, 'Statistics fetched successfully', [
                'total_agents' => $totalAgents,
                'active_agents' => $activeAgents,
                'inactive_agents' => $inactiveAgents,
                'issues_handled' => (int) $issuesHandled
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch agent statistics', 500, $e->getMessage());
        }
    }

    /**
     * Generate random password
     */
    private function generatePassword(int $length = 12): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        return substr(str_shuffle($chars), 0, $length);
    }
}
