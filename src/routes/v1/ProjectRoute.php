<?php

declare(strict_types=1);

use App\Controllers\ProjectController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use Slim\App;

/**
 * Project Routes
 *
 * Development projects
 * Prefix: /v1/projects
 */

return function (App $app) {
    $controller = $app->getContainer()->get(ProjectController::class);
    $authMiddleware = $app->getContainer()->get(AuthMiddleware::class);

    // Public routes
    $app->get('/v1/projects', [$controller, 'index']);
    $app->get('/v1/projects/featured', [$controller, 'featured']);
    $app->get('/v1/projects/stats', [$controller, 'stats']);
    $app->get('/v1/projects/{slug}', [$controller, 'showBySlug']);

    // Admin routes (require web_admin, officer, admin, mp, super_admin role)
    $app->group('/v1/admin/projects', function ($group) use ($controller) {
        $group->get('', [$controller, 'adminIndex']);
        $group->get('/{id:[0-9]+}', [$controller, 'show']);
        $group->post('', [$controller, 'store']);
        $group->put('/{id}', [$controller, 'update']);
        $group->delete('/{id}', [$controller, 'destroy']);
    })->add(new RoleMiddleware(['web_admin', 'officer', 'admin', 'mp', 'super_admin']))->add($authMiddleware);
};
