<?php

declare(strict_types=1);

use App\Controllers\ConstituentController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Models\User;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

/**
 * Constituent Routes (v1 API)
 */
return function (App $app): void {
    $container = $app->getContainer();
    $constituentController = $container->get(ConstituentController::class);
    $authMiddleware = $container->get(AuthMiddleware::class);
    
    $app->group('/v1/constituents', function (RouteCollectorProxy $group) use ($constituentController) {
        
        // List and View
        $group->get('', [$constituentController, 'index']);
        $group->get('/{id}', [$constituentController, 'show']);

        // Management
        $group->post('', [$constituentController, 'create']);
        $group->put('/{id}', [$constituentController, 'update']);
        
        // Only Super Admins and Admins can delete records
        $group->delete('/{id}', [$constituentController, 'delete'])
            ->add(new RoleMiddleware([User::ROLE_ADMIN]));
            
    })->add($authMiddleware);
};
