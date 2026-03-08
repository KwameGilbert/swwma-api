<?php

declare(strict_types=1);

use App\Controllers\OfficerDashboardController;
use App\Controllers\IssueController;
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
    $issueController = $container->get(IssueController::class);
    
    $app->group('/v1/officer', function (RouteCollectorProxy $group) use ($officerDashboardController, $issueController) {
        
        $group->get('/dashboard/stats', [$officerDashboardController, 'getStats']);

        $group->group('/reports', function (RouteCollectorProxy $reportsGroup) use ($officerDashboardController) {
            $reportsGroup->get('/summary', [$officerDashboardController, 'getSummary']);
            $reportsGroup->get('/breakdown', [$officerDashboardController, 'getBreakdown']);
            $reportsGroup->get('/recent-activity', [$officerDashboardController, 'getRecentActivity']);
            $reportsGroup->get('/trends', [$officerDashboardController, 'getTrends']);
            $reportsGroup->get('/status-distribution', [$officerDashboardController, 'getStatusDistribution']);
            $reportsGroup->get('/agent-performance', [$officerDashboardController, 'getAgentPerformance']);
            $reportsGroup->get('/profile-stats', [$officerDashboardController, 'getProfileStats']);
        });

        // Issues Management for Officers
        $group->get('/issues', [$officerDashboardController, 'getOfficerIssues']);
        $group->post('/issues', [$issueController, 'create']);
        $group->get('/issues/{id}', [$issueController, 'show']);
        $group->put('/issues/{id}', [$issueController, 'update']);
        $group->delete('/issues/{id}', [$issueController, 'delete']);

    })->add(new RoleMiddleware([User::ROLE_OFFICER, User::ROLE_ADMIN]))
      ->add($authMiddleware);
};
