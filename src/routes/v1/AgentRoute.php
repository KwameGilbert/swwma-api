<?php

declare(strict_types=1);

use App\Controllers\AgentDashboardController;
use App\Controllers\IssueController;
use App\Controllers\AuthController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Models\User;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

/**
 * Agent-specific Routes (v1 API)
 */
return function (App $app): void {
    $container = $app->getContainer();
    $authMiddleware = $container->get(AuthMiddleware::class);
    $agentController = $container->get(AgentDashboardController::class);
    $issueController = $container->get(IssueController::class);
    $authController = $container->get(AuthController::class);
    
    $app->group('/v1/agent', function (RouteCollectorProxy $group) use ($agentController, $issueController, $authController) {
        
        $group->get('/stats', [$agentController, 'getStats']);
        $group->get('/my-reports', [$agentController, 'getMyReports']);
        $group->get('/profile', [$authController, 'me']);
        $group->put('/profile', [$agentController, 'updateProfile']);
        
        // Issue Management for Agents
        $group->post('/issues', [$issueController, 'create']);
        $group->get('/issues/{id}', [$issueController, 'show']);
        $group->put('/issues/{id}', [$issueController, 'update']);
        $group->delete('/issues/{id}', [$issueController, 'delete']);
        
    })->add(new RoleMiddleware([User::ROLE_AGENT, User::ROLE_ADMIN]))
      ->add($authMiddleware);
};
