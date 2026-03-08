<?php

declare(strict_types=1);

use App\Controllers\LocationController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Models\User;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

/**
 * Location Routes (v1 API)
 */
return function (App $app): void {
    $container = $app->getContainer();
    $locationController = $container->get(LocationController::class);
    $authMiddleware = $container->get(AuthMiddleware::class);
    
    // Public get routes (optional: depending on if the app is public facing)
    $app->get('/v1/locations', [$locationController, 'index']);
    $app->get('/v1/locations/{id}', [$locationController, 'show']);

    // Protected management routes
    $app->group('/v1/locations', function (RouteCollectorProxy $group) use ($locationController) {
        $group->post('', [$locationController, 'create'])
            ->add(new RoleMiddleware([User::ROLE_ADMIN]));
            
        $group->put('/{id}', [$locationController, 'update'])
            ->add(new RoleMiddleware([User::ROLE_ADMIN]));
            
        $group->delete('/{id}', [$locationController, 'delete'])
            ->add(new RoleMiddleware([User::ROLE_ADMIN]));
            
    })->add($authMiddleware);
};
