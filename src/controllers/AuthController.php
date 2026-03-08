<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Models\WebAdminProfile;
use App\Models\OfficerProfile;
use App\Models\AgentProfile;
use App\Models\TaskForceProfile;
use App\Models\AdminProfile;
use App\Models\EmailVerificationToken;
use App\Helper\ResponseHelper;
use App\Services\AuthService;
use App\Services\ActivityLogService;
use App\Services\EmailService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as v;
use Exception;

/**
 * AuthController
 * 
 * Handles all authentication endpoints for the Constituency Development System.
 */
class AuthController
{
    private AuthService $authService;
    private ActivityLogService $activityLogger;

    public function __construct(AuthService $authService, ActivityLogService $activityLogger)
    {
        $this->authService = $authService;
        $this->activityLogger = $activityLogger;
    }

    /**
     * Register a new user
     * POST /auth/register
     */
    public function register(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            $metadata = $this->getRequestMetadata($request);

            // Validation
            $errors = $this->validateRegistration($data);
            if (!empty($errors)) {
                return ResponseHelper::error($response, 'Validation failed', 400, $errors);
            }

            // Check if user already exists
            if (User::findByEmail($data['email'])) {
                return ResponseHelper::error($response, 'Account already exists with this email', 409);
            }

            // Create user
            $user = User::create([
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $this->authService->hashPassword($data['password']),
                'role' => $data['role'] ?? User::ROLE_WEB_ADMIN,
                'status' => User::STATUS_ACTIVE,
                'email_verified' => false
            ]);

            // Create role-based profile
            $this->createRoleProfile($user, $data);

            // Log registration event
            $this->authService->logAuditEvent($user->id, 'register', $metadata);

            // Generate tokens
            $userPayload = $this->authService->generateUserPayload($user);
            $accessToken = $this->authService->generateAccessToken($userPayload);
            $refreshToken = $this->authService->createRefreshToken($user->id, $metadata);

            return ResponseHelper::success($response, 'User registered successfully', [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->getFullName(),
                    'role' => $user->role,
                    'status' => $user->status,
                ],
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'expires_in' => $this->authService->getTokenExpiry()
            ], 201);

        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Registration failed', 500, $e->getMessage());
        }
    }

    /**
     * Create role-based profile for a new user
     */
    private function createRoleProfile(User $user, array $data): void
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
                WebAdminProfile::create($profileData);
                break;
            case User::ROLE_OFFICER:
                OfficerProfile::create($profileData);
                break;
            case User::ROLE_AGENT:
                AgentProfile::create($profileData);
                break;
            case User::ROLE_TASK_FORCE:
                TaskForceProfile::create($profileData);
                break;
            case User::ROLE_ADMIN:
                AdminProfile::create($profileData);
                break;
        }
    }

    /**
     * Login user
     * POST /auth/login
     */
    public function login(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            $metadata = $this->getRequestMetadata($request);

            if (empty($data['email']) || empty($data['password'])) {
                return ResponseHelper::error($response, 'Email and password are required', 400);
            }

            $user = User::findByEmail($data['email']);

            if (!$user) {
                return ResponseHelper::error($response, 'Invalid credentials', 401);
            }

            if (!$this->authService->verifyPassword($data['password'], $user->password)) {
                $this->authService->logAuditEvent($user->id, 'login_failed', $metadata);
                return ResponseHelper::error($response, 'Invalid credentials', 401);
            }

            if (!$user->isActive()) {
                return ResponseHelper::error($response, 'Account is suspended or pending', 403);
            }

            // Generate tokens
            $userPayload = $this->authService->generateUserPayload($user);
            $accessToken = $this->authService->generateAccessToken($userPayload);
            $refreshToken = $this->authService->createRefreshToken($user->id, $metadata);

            // Log successful login
            $this->authService->logAuditEvent($user->id, 'login', $metadata);

            return ResponseHelper::success($response, 'Login successful', [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->getFullName(),
                    'role' => $user->role,
                ],
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_in' => $this->authService->getTokenExpiry(),
                'token_type' => 'Bearer',
            ]);

        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Login failed', 500, $e->getMessage());
        }
    }

    /**
     * Get current authenticated user details
     * GET /auth/me
     */
    public function me(Request $request, Response $response): Response
    {
        try {
            $userData = $request->getAttribute('user');

            if (!$userData) {
                return ResponseHelper::error($response, 'Unauthenticated', 401);
            }

            $user = User::with(['webAdminProfile', 'officerProfile', 'agentProfile', 'taskForceProfile', 'adminProfile'])->find($userData->id);

            if (!$user) {
                return ResponseHelper::error($response, 'User not found', 404);
            }

            $profile = $user->getProfile();

            return ResponseHelper::success($response, 'User profile fetched', [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'profile' => $profile ? $profile->toArray() : null
            ]);

        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch user', 500, $e->getMessage());
        }
    }

    /**
     * Change password
     * PUT /auth/password
     */
    public function changePassword(Request $request, Response $response): Response
    {
        try {
            $userData = $request->getAttribute('user');
            $data = $request->getParsedBody();

            if (empty($data['current_password']) || empty($data['new_password'])) {
                return ResponseHelper::error($response, 'Current and new password are required', 400);
            }

            $user = User::find($userData->id);
            if (!$user) {
                return ResponseHelper::error($response, 'User not found', 404);
            }

            // Verify current password
            if (!$this->authService->verifyPassword($data['current_password'], $user->password)) {
                return ResponseHelper::error($response, 'Current password is incorrect', 400);
            }

            // Update password
            $user->password = $data['new_password']; // Mutator handles hashing
            $user->save();

            // Log event
            $this->activityLogger->logUpdate($user->id, 'User', (int)$user->id, ['password' => 'MASKED'], ['password' => 'UPDATED']);

            return ResponseHelper::success($response, 'Password updated successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to update password', 500, $e->getMessage());
        }
    }

    /**
     * Refresh access token
     */
    public function refresh(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            if (empty($data['refresh_token'])) {
                return ResponseHelper::error($response, 'Refresh token required', 400);
            }

            $metadata = $this->getRequestMetadata($request);
            $tokens = $this->authService->refreshAccessToken($data['refresh_token'], $metadata);

            if (!$tokens) {
                return ResponseHelper::error($response, 'Invalid refresh token', 401);
            }

            return ResponseHelper::success($response, 'Token refreshed', $tokens);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Refresh failed', 500, $e->getMessage());
        }
    }

    /**
     * Logout
     */
    public function logout(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            if (!empty($data['refresh_token'])) {
                $this->authService->revokeRefreshToken($data['refresh_token']);
            }
            return ResponseHelper::success($response, 'Logged out');
        } catch (Exception $e) {
            return ResponseHelper::success($response, 'Logged out');
        }
    }

    /**
     * Registration validation logic
     */
    private function validateRegistration(array $data): array
    {
        $errors = [];

        if (empty($data['email']) || !v::email()->validate($data['email'])) {
            $errors['email'] = 'Valid email is required';
        }

        if (empty($data['password']) || strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }

        if (empty($data['first_name'])) {
            $errors['first_name'] = 'First name is required';
        }

        if (empty($data['last_name'])) {
            $errors['last_name'] = 'Last name is required';
        }

        $validRoles = [User::ROLE_ADMIN, User::ROLE_WEB_ADMIN, User::ROLE_OFFICER, User::ROLE_AGENT, User::ROLE_TASK_FORCE];
        if (!empty($data['role']) && !in_array($data['role'], $validRoles)) {
            $errors['role'] = 'Invalid role';
        }

        return $errors;
    }

    private function getRequestMetadata(Request $request): array
    {
        $serverParams = $request->getServerParams();
        return [
            'ip_address' => $serverParams['REMOTE_ADDR'] ?? null,
            'user_agent' => $request->getHeaderLine('User-Agent'),
            'device_name' => $request->getHeaderLine('X-Device-Name')
        ];
    }
}
