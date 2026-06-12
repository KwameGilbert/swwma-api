<?php

declare(strict_types=1);

use App\Controllers\AgentController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use Slim\App;

/**
 * Agent Routes
 * 
 * Agent management and dashboard
 * Prefix: /v1/agent, /v1/admin/agents
 */

return function (App $app) {
    $controller = $app->getContainer()->get(AgentController::class);
    $authMiddleware = $app->getContainer()->get(AuthMiddleware::class);

    // Agent dashboard routes (require agent role)
    $app->group('/v1/agent', function ($group) use ($controller) {
        $group->get('/profile', [$controller, 'profile']);
        $group->put('/profile', [$controller, 'updateProfile']);
        $group->put('/password', [$controller, 'changePassword']);
        $group->get('/my-reports', [$controller, 'myReports']);
        $group->get('/stats', [$controller, 'getStats']);
    })->add(new RoleMiddleware(['agent', 'web_admin', 'admin', 'officer']))->add($authMiddleware);

    // Admin routes (require web_admin or officer role)
    $app->group('/v1/admin/agents', function ($group) use ($controller) {
        $group->get('', [$controller, 'index']);
        $group->get('/stats', [$controller, 'getStatistics']);
        $group->get('/{id}', [$controller, 'show']);
        $group->post('', [$controller, 'store']);
        $group->put('/{id}', [$controller, 'update']);
        $group->post('/{id}/verify', [$controller, 'verify']);
        $group->delete('/{id}', [$controller, 'destroy']);
    })->add(new RoleMiddleware(['web_admin', 'officer', 'admin']))->add($authMiddleware);
};
