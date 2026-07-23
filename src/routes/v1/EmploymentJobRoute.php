<?php

declare(strict_types=1);

use App\Controllers\EmploymentJobController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use Slim\App;

/**
 * Employment Job Routes
 * 
 * Endpoints for managing employment job listings
 * 
 * Public Endpoints:
 * - GET /v1/jobs/public - Get open job listings (public)
 * - GET /v1/jobs/{id} - Get single job (public)
 * 
 * Admin Endpoints:
 * - GET /v1/jobs - List all jobs
 * - POST /v1/jobs - Create job
 * - PUT /v1/jobs/{id} - Update job
 * - DELETE /v1/jobs/{id} - Delete job
 * - POST /v1/jobs/{id}/publish - Publish job
 * - POST /v1/jobs/{id}/close - Close job
 */

return function (App $app) {
    $controller = $app->getContainer()->get(EmploymentJobController::class);
    $authMiddleware = $app->getContainer()->get(AuthMiddleware::class);

    // Public routes (no authentication required)
    $app->get('/v1/jobs/public', [$controller, 'publicList']);
    $app->get('/v1/jobs/{id}', [$controller, 'show']);

    // Admin routes (require admin or web_admin role)
    $adminRoutes = function ($group) use ($controller) {
        $group->get('', [$controller, 'index']);
        $group->post('', [$controller, 'store']);
        $group->put('/{id}', [$controller, 'update']);
        $group->delete('/{id}', [$controller, 'destroy']);
        $group->post('/{id}/publish', [$controller, 'publish']);
        $group->post('/{id}/close', [$controller, 'close']);
        // Applicants management
        $group->get('/{id}/applicants', [$controller, 'getApplicants']);
        $group->put('/{id}/applicants/{applicantId}', [$controller, 'updateApplicantStatus']);
    };

    $allowedRoles = new RoleMiddleware(['admin', 'web_admin', 'mp', 'super_admin', 'officer']);

    $app->group('/v1/jobs', $adminRoutes)->add($allowedRoles)->add($authMiddleware);
    $app->group('/v1/admin/jobs', $adminRoutes)->add($allowedRoles)->add($authMiddleware);
};
