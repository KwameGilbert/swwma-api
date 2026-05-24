<?php

declare(strict_types=1);

use App\Controllers\IssueReportController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use Slim\App;

/**
 * Issue Report Routes
 * 
 * Community issue tracking with full workflow
 * Prefix: /v1/issues
 */

return function (App $app) {
    $controller = $app->getContainer()->get(IssueReportController::class);
    $authMiddleware = $app->getContainer()->get(AuthMiddleware::class);

    // Public routes - DISABLED FOR NOW
    // $app->post('/v1/issues', [$controller, 'submit']);
    // $app->get('/v1/issues/track/{caseId}', [$controller, 'track']);

    // Agent routes (require agent role)
    $app->group('/v1/agent/issues', function ($group) use ($controller) {
        $group->post('', [$controller, 'agentSubmit']);
        $group->get('/{id}', [$controller, 'agentShow']);
        $group->put('/{id}', [$controller, 'agentUpdate']);
        $group->delete('/{id}', [$controller, 'agentDelete']);
    })->add(new RoleMiddleware(['agent']))->add($authMiddleware);

    // Officer routes (require officer role)
    $app->group('/v1/officer/issues', function ($group) use ($controller) {
        $group->get('', [$controller, 'index']);
        $group->post('', [$controller, 'officerSubmit']);
        $group->put('/{id}', [$controller, 'officerUpdate']);
        $group->delete('/{id}', [$controller, 'officerDelete']);
        $group->put('/{id}/forward', [$controller, 'officerForward']);
    })->add(new RoleMiddleware(['officer']))->add($authMiddleware);

    // Admin routes - Viewing & Basic Management (web_admin, admin, officer or task_force)
    $app->group('/v1/admin/issues', function ($group) use ($controller) {
        $group->get('', [$controller, 'index']);
        $group->get('/stats', [$controller, 'stats']);
        $group->get('/awaiting-action', [$controller, 'awaitingAction']);
        $group->get('/{id}', [$controller, 'show']);
        $group->put('/{id}/status', [$controller, 'updateStatus']);
        $group->put('/{id}/assign', [$controller, 'assign']);
        $group->post('/{id}/comments', [$controller, 'addComment']);
    })->add(new RoleMiddleware(['admin', 'web_admin', 'officer', 'task_force']))->add($authMiddleware);

    // Admin routes - Task Force Workflow (admin or web_admin)
    $app->group('/v1/admin/issues', function ($group) use ($controller) {
        $group->put('/{id}/assign-task-force', [$controller, 'assignToTaskForce']);
        $group->put('/{id}/allocate-resources', [$controller, 'allocateResources']);
        $group->put('/{id}/review-assessment', [$controller, 'reviewAssessment']);
        $group->put('/{id}/review-resolution', [$controller, 'reviewResolution']);
    })->add(new RoleMiddleware(['admin', 'web_admin']))->add($authMiddleware);
};
