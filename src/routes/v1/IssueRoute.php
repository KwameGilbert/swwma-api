<?php

declare(strict_types=1);

use App\Controllers\IssueController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Models\User;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

/**
 * Issue Tracking Routes (v1 API)
 */
return function (App $app): void {
    $container = $app->getContainer();
    $issueController = $container->get(IssueController::class);
    $authMiddleware = $container->get(AuthMiddleware::class);
    
    $app->group('/v1/issues', function (RouteCollectorProxy $group) use ($issueController) {
        
        // Reporting and Viewing
        $group->get('', [$issueController, 'index']);
        $group->get('/{id}', [$issueController, 'show']);
        $group->post('', [$issueController, 'create']);

        // Management (Updates)
        $group->put('/{id}', [$issueController, 'update']);
        $group->patch('/{id}/status', [$issueController, 'updateStatus']);
        $group->patch('/{id}/review', [$issueController, 'review'])
            ->add(new RoleMiddleware([User::ROLE_ADMIN]));
        
        // Deletion (Protected - Admins only)
        $group->delete('/{id}', [$issueController, 'delete'])
            ->add(new RoleMiddleware([User::ROLE_ADMIN]));

        // --- SUB-RESOURCES ---

        // Assessments (Report by Officer/Agent/Supervisor)
        $group->get('/{id}/assessment', [\App\Controllers\IssueAssessmentController::class, 'show']);
        $group->post('/{id}/assessment', [\App\Controllers\IssueAssessmentController::class, 'createOrUpdate']);
        $group->patch('/{id}/assessment/status', [\App\Controllers\IssueAssessmentController::class, 'updateStatus'])
            ->add(new RoleMiddleware([User::ROLE_ADMIN]));

        // Resolutions (Final Report)
        $group->get('/{id}/resolution', [\App\Controllers\IssueResolutionController::class, 'show']);
        $group->post('/{id}/resolution', [\App\Controllers\IssueResolutionController::class, 'createOrUpdate']);

        // Resource Allocations (Admin Only)
        $group->get('/{id}/allocations', [\App\Controllers\IssueAllocationController::class, 'show']);
        $group->post('/{id}/allocate', [\App\Controllers\IssueAllocationController::class, 'allocate'])
            ->add(new RoleMiddleware([User::ROLE_ADMIN]));
            
    })->add($authMiddleware);
};
