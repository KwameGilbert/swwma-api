<?php

declare(strict_types=1);

use App\Controllers\EmploymentController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Models\User;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

/**
 * Employment and Job Routes (v1 API)
 */
return function (App $app): void {
    $container = $app->getContainer();
    $employmentController = $container->get(EmploymentController::class);
    $authMiddleware = $container->get(AuthMiddleware::class);
    
    // Public get routes
    $app->get('/v1/jobs', [$employmentController, 'listJobs']);
    $app->get('/v1/jobs/{id}', [$employmentController, 'showJob']);
    $app->post('/v1/jobs/{id}/apply', [$employmentController, 'apply']);

    // Protected management routes
    $app->group('/v1/employment', function (RouteCollectorProxy $group) use ($employmentController) {
        
        // Job Listings
        $group->post('/jobs', [$employmentController, 'createJob']);
        $group->put('/jobs/{id}', [$employmentController, 'updateJob']);
        
        // Applicant Management
        $group->get('/applicants', [$employmentController, 'listApplicants']);
        $group->patch('/applicants/{id}/status', [$employmentController, 'updateApplicantStatus']);
            
    })->add(new RoleMiddleware([User::ROLE_ADMIN, User::ROLE_WEB_ADMIN]))
      ->add($authMiddleware);
};
