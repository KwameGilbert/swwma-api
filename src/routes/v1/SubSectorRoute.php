<?php

declare(strict_types=1);

use App\Controllers\SubSectorController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Models\User;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = $app->getContainer();
    $subsectorController = $container->get(SubSectorController::class);
    $authMiddleware = $container->get(AuthMiddleware::class);
    
    // Public routes
    $app->get('/v1/sub-sectors', [$subsectorController, 'index']);
    $app->get('/v1/sub-sectors/{id}', [$subsectorController, 'show']);

    // Protected routes
    $app->group('/v1/sub-sectors', function (RouteCollectorProxy $group) use ($subsectorController) {
        $group->post('', [$subsectorController, 'create'])
            ->add(new RoleMiddleware([User::ROLE_ADMIN]));
        $group->put('/{id}', [$subsectorController, 'update'])
            ->add(new RoleMiddleware([User::ROLE_ADMIN]));
        $group->delete('/{id}', [$subsectorController, 'delete'])
            ->add(new RoleMiddleware([User::ROLE_ADMIN]));
    })->add($authMiddleware);
};
