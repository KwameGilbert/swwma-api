<?php

declare(strict_types=1);

use App\Controllers\OfficerController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use Slim\App;

/**
 * Officer Routes
 * 
 * Officer management and dashboard
 * Prefix: /v1/officers, /v1/admin/officers
 */

return function (App $app) {
    $controller = $app->getContainer()->get(OfficerController::class);
    $authMiddleware = $app->getContainer()->get(AuthMiddleware::class);

    // Officer dashboard routes (require officer role)
    $app->group('/v1/officer', function ($group) use ($controller) {
        $group->get('/reports', [$controller, 'myReports']);
        $group->get('/agents', [$controller, 'myAgents']);
        $group->get('/management/agents', [$controller, 'getManagementAgents']);
        $group->get('/management/agents/stats', [$controller, 'getAgentStats']);
    })->add(new RoleMiddleware(['officer']))->add($authMiddleware);

    // Admin routes (require web_admin role)
    $app->group('/v1/admin/officers', function ($group) use ($controller) {
        $group->get('', [$controller, 'index']);
        $group->get('/{id}', [$controller, 'show']);
        $group->post('', [$controller, 'store']);
        $group->put('/{id}', [$controller, 'update']);
        $group->post('/{id}', [$controller, 'update']); // For file uploads with _method=PUT
        $group->delete('/{id}', [$controller, 'destroy']);
    })->add(new RoleMiddleware(['web_admin', 'admin']))->add($authMiddleware);
};
