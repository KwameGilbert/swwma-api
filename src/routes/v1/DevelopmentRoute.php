<?php

declare(strict_types=1);

use App\Controllers\DevelopmentManagementController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Models\User;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

/**
 * Development Routes (Projects and Events) (v1 API)
 */
return function (App $app): void {
    $container = $app->getContainer();
    $developmentController = $container->get(DevelopmentManagementController::class);
    $authMiddleware = $container->get(AuthMiddleware::class);
    
    // Public viewing routes
    $app->get('/v1/projects', [$developmentController, 'listProjects']);
    $app->get('/v1/projects/{id}', [$developmentController, 'showProject']);
    $app->get('/v1/events', [$developmentController, 'listEvents']);

    // Protected management routes
    $app->group('/v1/development', function (RouteCollectorProxy $group) use ($developmentController) {
        
        // Projects
        $group->post('/projects', [$developmentController, 'createProject']);
        $group->put('/projects/{id}', [$developmentController, 'updateProject']);
        
        // Events
        $group->post('/events', [$developmentController, 'createEvent']);
        $group->put('/events/{id}', [$developmentController, 'updateEvent']);
        $group->delete('/events/{id}', [$developmentController, 'deleteEvent']);
            
    })->add(new RoleMiddleware([User::ROLE_ADMIN, User::ROLE_WEB_ADMIN]))
      ->add($authMiddleware);
};
