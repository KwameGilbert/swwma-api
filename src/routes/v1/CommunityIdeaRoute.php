<?php

declare(strict_types=1);

use App\Controllers\CommunityIdeaController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use Slim\App;

/**
 * Community Idea Routes
 * 
 * Endpoints for managing community ideas
 * 
 * Public Endpoints:
 * - GET /v1/ideas/public - Get approved/implemented ideas (public)
 * - GET /v1/ideas/top - Get top voted ideas (public)
 * - GET /v1/ideas/{id} - Get single idea (public)
 * - POST /v1/ideas - Submit new idea (public or authenticated)
 * - POST /v1/ideas/{id}/vote - Vote for an idea (public or authenticated)
 * 
 * Authenticated Endpoints:
 * - DELETE /v1/ideas/{id}/vote - Remove vote (authenticated)
 * 
 * Admin Endpoints:
 * - GET /v1/ideas - List all ideas
 * - PUT /v1/ideas/{id} - Update idea
 * - DELETE /v1/ideas/{id} - Delete idea
 * - POST /v1/ideas/{id}/status - Change idea status
 */

return function (App $app) {
    $controller = $app->getContainer()->get(CommunityIdeaController::class);
    $authMiddleware = $app->getContainer()->get(AuthMiddleware::class);

    // Public routes (no authentication required)
    $app->get('/v1/ideas/public', [$controller, 'publicList']);
    $app->get('/v1/ideas/top', [$controller, 'topVoted']);
    $app->get('/v1/ideas/{id}', [$controller, 'show']);
    $app->post('/v1/ideas', [$controller, 'store']); // Allow public submissions
    $app->post('/v1/ideas/{id}/vote', [$controller, 'vote']); // Allow public voting

    // Authenticated routes (require login)
    $app->delete('/v1/ideas/{id}/vote', [$controller, 'unvote'])
        ->add($authMiddleware);

    // Admin routes (require admin or web_admin role)
    $adminIdeaRoutes = function ($group) use ($controller) {
        $group->get('', [$controller, 'index']);
        $group->put('/{id}', [$controller, 'update']);
        $group->delete('/{id}', [$controller, 'destroy']);
        $group->post('/{id}/status', [$controller, 'updateStatus']);
    };

    $allowedIdeaRoles = new RoleMiddleware(['admin', 'web_admin', 'mp', 'super_admin', 'officer']);

    $app->group('/v1/ideas', $adminIdeaRoutes)->add($allowedIdeaRoles)->add($authMiddleware);
    $app->group('/v1/admin/ideas', $adminIdeaRoutes)->add($allowedIdeaRoles)->add($authMiddleware);
};
