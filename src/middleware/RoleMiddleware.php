<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Helper\ResponseHelper;
use App\Models\User;

/**
 * Role-based Access Control Middleware
 */
class RoleMiddleware
{
    private array $allowedRoles;

    /**
     * @param array $allowedRoles Array of roles that can access the route
     */
    public function __construct(array $allowedRoles)
    {
        $this->allowedRoles = $allowedRoles;
    }

    /**
     * Process incoming request and check if user role is allowed
     */
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $userPayload = $request->getAttribute('user');

        if (!$userPayload) {
            $response = new \Slim\Psr7\Response();
            return ResponseHelper::error($response, 'Unauthenticated', 401);
        }

        // Super Admin has access to everything
        if ($userPayload->role === User::ROLE_ADMIN) {
            return $handler->handle($request);
        }

        if (!in_array($userPayload->role, $this->allowedRoles)) {
            $response = new \Slim\Psr7\Response();
            return ResponseHelper::error($response, 'Forbidden: You do not have permission to access this resource', 403);
        }

        return $handler->handle($request);
    }
}
