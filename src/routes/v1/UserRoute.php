<?php

declare(strict_types=1);

use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Models\User;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

/**
 * User Routes (v1 API)
 */
return function (App $app): void {
    $container = $app->getContainer();
    $userController = $container->get(UserController::class);
    $authMiddleware = $container->get(AuthMiddleware::class);
    
    // Grouped routes under /v1/users with Auth protection
    $app->group('/v1/users', function (RouteCollectorProxy $group) use ($userController, $container) {
        $group->get('', [$userController, 'index'])
            ->add(new RoleMiddleware([User::ROLE_ADMIN])); // Only admins can list users
            
        $group->get('/{id}', [$userController, 'show']);
        
        $group->post('', [$userController, 'create'])
            ->add(new RoleMiddleware([User::ROLE_ADMIN])); // Only admins can create users
            
        $group->put('/{id}', [$userController, 'update']);
        
        $group->delete('/{id}', [$userController, 'delete']);
    })->add($authMiddleware);
};