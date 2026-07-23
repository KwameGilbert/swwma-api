<?php

declare(strict_types=1);

use App\Controllers\DashboardController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use Slim\App;

/**
 * Dashboard Routes
 * 
 * Role-specific dashboard statistics endpoints
 * Each endpoint requires specific role authentication
 * Prefix: /v1/admin/dashboard, /v1/officer/dashboard, /v1/agent/dashboard, /v1/task-force/dashboard
 */

return function (App $app) {
    $controller = $app->getContainer()->get(DashboardController::class);
    $authMiddleware = $app->getContainer()->get(AuthMiddleware::class);

    // Admin dashboard routes (require admin, web_admin, mp, super_admin, officer role)
    $app->group('/v1/admin/dashboard', function ($group) use ($controller) {
        // GET /v1/admin/dashboard/stats - Get admin dashboard statistics
        $group->get('/stats', [$controller, 'adminStats']);
        // GET /v1/admin/dashboard/finance - Get finance overview data
        $group->get('/finance', [$controller, 'financeOverview']);
    })->add(new RoleMiddleware(['admin', 'web_admin', 'mp', 'super_admin', 'officer']))->add($authMiddleware);

    // Officer dashboard routes (require officer role)
    $app->group('/v1/officer/dashboard', function ($group) use ($controller) {
        // GET /v1/officer/dashboard/stats - Get officer dashboard statistics
        $group->get('/stats', [$controller, 'officerStats']);
    })->add(new RoleMiddleware(['officer']))->add($authMiddleware);

    // Agent dashboard routes (require agent role)
    $app->group('/v1/agent/dashboard', function ($group) use ($controller) {
        // GET /v1/agent/dashboard/stats - Get agent dashboard statistics
        $group->get('/stats', [$controller, 'agentStats']);
    })->add(new RoleMiddleware(['agent']))->add($authMiddleware);

    // Task Force dashboard routes (require task_force role)
    $app->group('/v1/task-force/dashboard', function ($group) use ($controller) {
        // GET /v1/task-force/dashboard/stats - Get task force dashboard statistics
        $group->get('/stats', [$controller, 'taskForceStats']);
    })->add(new RoleMiddleware(['task_force']))->add($authMiddleware);
};
