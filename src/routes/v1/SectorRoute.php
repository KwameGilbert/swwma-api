<?php

declare(strict_types=1);

use App\Controllers\SectorController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Models\User;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = $app->getContainer();
    $sectorController = $container->get(SectorController::class);
    $authMiddleware = $container->get(AuthMiddleware::class);
    
    // Public routes
    $app->get('/v1/sectors', [$sectorController, 'index']);
    $app->get('/v1/sectors/{id}', [$sectorController, 'show']);

    // Protected routes
    $app->group('/v1/sectors', function (RouteCollectorProxy $group) use ($sectorController) {
        $group->post('', [$sectorController, 'create'])
            ->add(new RoleMiddleware([User::ROLE_ADMIN]));
        $group->put('/{id}', [$sectorController, 'update'])
            ->add(new RoleMiddleware([User::ROLE_ADMIN]));
        $group->delete('/{id}', [$sectorController, 'delete'])
            ->add(new RoleMiddleware([User::ROLE_ADMIN]));
    })->add($authMiddleware);
};
