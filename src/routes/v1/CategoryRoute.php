<?php

declare(strict_types=1);

use App\Controllers\CategoryController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Models\User;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = $app->getContainer();
    $categoryController = $container->get(CategoryController::class);
    $authMiddleware = $container->get(AuthMiddleware::class);
    
    // Public routes
    $app->get('/v1/categories', [$categoryController, 'index']);
    $app->get('/v1/categories/{id}', [$categoryController, 'show']);

    // Protected routes
    $app->group('/v1/categories', function (RouteCollectorProxy $group) use ($categoryController) {
        $group->post('', [$categoryController, 'create'])
            ->add(new RoleMiddleware([User::ROLE_ADMIN]));
        $group->put('/{id}', [$categoryController, 'update'])
            ->add(new RoleMiddleware([User::ROLE_ADMIN]));
        $group->delete('/{id}', [$categoryController, 'delete'])
            ->add(new RoleMiddleware([User::ROLE_ADMIN]));
    })->add($authMiddleware);
};
