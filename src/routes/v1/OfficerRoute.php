<?php

declare(strict_types=1);

use App\Controllers\OfficerDashboardController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Models\User;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

/**
 * Officer-specific Routes (v1 API)
 */
return function (App $app): void {
    $container = $app->getContainer();
    $authMiddleware = $container->get(AuthMiddleware::class);
    $officerDashboardController = $container->get(OfficerDashboardController::class);
    
    $app->group('/v1/officer/reports', function (RouteCollectorProxy $group) use ($officerDashboardController) {
        
        $group->get('/summary', [$officerDashboardController, 'getSummary']);
        $group->get('/breakdown', [$officerDashboardController, 'getBreakdown']);
        $group->get('/recent-activity', [$officerDashboardController, 'getRecentActivity']);
        $group->get('/trends', [$officerDashboardController, 'getTrends']);
        $group->get('/status-distribution', [$officerDashboardController, 'getStatusDistribution']);
        $group->get('/agent-performance', [$officerDashboardController, 'getAgentPerformance']);
        $group->get('/profile-stats', [$officerDashboardController, 'getProfileStats']);
        
    })->add(new RoleMiddleware([User::ROLE_OFFICER, User::ROLE_ADMIN]))
      ->add($authMiddleware);
};
