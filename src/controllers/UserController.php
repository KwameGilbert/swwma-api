<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Models\WebAdminProfile;
use App\Models\OfficerProfile;
use App\Models\AgentProfile;
use App\Models\TaskForceProfile;
use App\Models\AdminProfile;
use App\Helper\ResponseHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\ActivityLogService;
use Exception;
use Respect\Validation\Validator as v;

/**
 * UserController
 * Handles user-related operations for the Constituency Development System.
 */
class UserController
{
    private ActivityLogService $activityLogger;

    public function __construct(ActivityLogService $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    /**
     * Get all users (with profiles)
     * GET /v1/users
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $users = User::with(['webAdminProfile', 'officerProfile', 'agentProfile', 'taskForceProfile', 'adminProfile'])->get();
            
            return ResponseHelper::success($response, 'Users fetched successfully', [
                'users' => $users,
                'count' => $users->count()
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch users', 500, $e->getMessage());
        }
    }

    /**
     * Get single user by ID
     * GET /v1/users/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'];
            $user = User::with(['webAdminProfile', 'officerProfile', 'agentProfile', 'taskForceProfile', 'adminProfile'])->find($id);
            
            if (!$user) {
                return ResponseHelper::error($response, 'User not found', 404);
            }
            
            $userData = $user->toArray();
            $userData['profile'] = $user->getProfile();
            
            return ResponseHelper::success($response, 'User fetched successfully', $userData);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch user', 500, $e->getMessage());
        }
    }

    /**
     * Create new user and profile (Admin only)
     * POST /v1/users
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            // Validation
            if (empty($data['email']) || empty($data['password']) || empty($data['first_name']) || empty($data['last_name'])) {
                return ResponseHelper::error($response, 'Email, password, first name and last name are required', 400);
            }
            
            if (User::findByEmail($data['email'])) {
                return ResponseHelper::error($response, 'Email already exists', 409);
            }
            
            $user = User::create([
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'], // Mutator hashes it
                'role' => $data['role'] ?? User::ROLE_WEB_ADMIN,
                'status' => $data['status'] ?? User::STATUS_ACTIVE,
                'email_verified' => true // Admin created users can be auto-verified
            ]);

            // Create Profile
            $this->saveProfile($user, $data);
            
            return ResponseHelper::success($response, 'User created successfully', $user->load(['webAdminProfile', 'officerProfile', 'agentProfile', 'taskForceProfile', 'adminProfile'])->toArray(), 201);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to create user', 500, $e->getMessage());
        }
    }

    /**
     * Update user and profile
     * PUT /v1/users/{id}
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'];
            $data = $request->getParsedBody();
            
            $user = User::find($id);
            if (!$user) {
                return ResponseHelper::error($response, 'User not found', 404);
            }

            // Authorization
            $auth = $request->getAttribute('user');
            if ($auth->role !== User::ROLE_ADMIN && (int)$id !== $auth->id) {
                return ResponseHelper::error($response, 'Unauthorized', 403);
            }
            
            // Update User fields
            $userUpdates = array_intersect_key($data, array_flip(['email', 'phone', 'status', 'role']));
            
            if (isset($userUpdates['email']) && $userUpdates['email'] !== $user->email) {
                if (User::where('email', $userUpdates['email'])->where('id', '!=', $id)->exists()) {
                    return ResponseHelper::error($response, 'Email already in use', 409);
                }
            }

            // Only Admins can change roles or status
            if ($auth->role !== User::ROLE_ADMIN) {
                unset($userUpdates['role'], $userUpdates['status']);
            }

            $user->update($userUpdates);

            // Update Profile fields
            $profile = $user->getProfile();
            if ($profile) {
                $profileUpdates = array_intersect_key($data, array_flip(['first_name', 'last_name', 'gender', 'profile_image']));
                $profile->update($profileUpdates);
            } else {
                // If profile doesn't exist for some reason, create it
                $this->saveProfile($user, $data);
            }
            
            return ResponseHelper::success($response, 'User updated successfully', $user->fresh(['webAdminProfile', 'officerProfile', 'agentProfile', 'taskForceProfile', 'adminProfile'])->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to update user', 500, $e->getMessage());
        }
    }

    /**
     * Delete user
     * DELETE /v1/users/{id}
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'];
            $user = User::find($id);
            
            if (!$user) {
                return ResponseHelper::error($response, 'User not found', 404);
            }

            // Authorization
            $auth = $request->getAttribute('user');
            if ($auth->role !== User::ROLE_ADMIN && (int)$id !== $auth->id) {
                return ResponseHelper::error($response, 'Unauthorized', 403);
            }
            
            $user->delete(); // Profiles will be deleted via CASCADE constraint
            return ResponseHelper::success($response, 'User deleted successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to delete user', 500, $e->getMessage());
        }
    }

    /**
     * Helper to save role-specific profile
     */
    private function saveProfile(User $user, array $data): void
    {
        $profileData = [
            'user_id' => $user->id,
            'first_name' => $data['first_name'] ?? 'User',
            'last_name' => $data['last_name'] ?? (string)$user->id,
            'gender' => $data['gender'] ?? null,
            'profile_image' => $data['profile_image'] ?? null,
        ];

        switch ($user->role) {
            case User::ROLE_WEB_ADMIN:
                WebAdminProfile::updateOrCreate(['user_id' => $user->id], $profileData);
                break;
            case User::ROLE_OFFICER:
                OfficerProfile::updateOrCreate(['user_id' => $user->id], $profileData);
                break;
            case User::ROLE_AGENT:
                AgentProfile::updateOrCreate(['user_id' => $user->id], $profileData);
                break;
            case User::ROLE_TASK_FORCE:
                TaskForceProfile::updateOrCreate(['user_id' => $user->id], $profileData);
                break;
            case User::ROLE_ADMIN:
                AdminProfile::updateOrCreate(['user_id' => $user->id], $profileData);
                break;
        }
    }
}
