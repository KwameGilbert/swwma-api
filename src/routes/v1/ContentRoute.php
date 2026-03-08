<?php

declare(strict_types=1);

use App\Controllers\ContentManagementController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Models\User;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

/**
 * Content Routes (v1 API)
 */
return function (App $app): void {
    $container = $app->getContainer();
    $contentController = $container->get(ContentManagementController::class);
    $authMiddleware = $container->get(AuthMiddleware::class);
    
    // Public get routes
    $app->get('/v1/community-ideas', [$contentController, 'listIdeas']);
    $app->get('/v1/blog-posts', [$contentController, 'listBlogs']);

    // Protected management routes
    $app->group('/v1/content', function (RouteCollectorProxy $group) use ($contentController) {
        
        // Ideas (Creation is open to all auth users)
        $group->post('/ideas', [$contentController, 'createIdea']);

        // Blogs (Admin only)
        $group->post('/blogs', [$contentController, 'createBlog'])
            ->add(new RoleMiddleware([User::ROLE_ADMIN, User::ROLE_WEB_ADMIN]));
        $group->put('/blogs/{id}', [$contentController, 'updateBlog'])
            ->add(new RoleMiddleware([User::ROLE_ADMIN, User::ROLE_WEB_ADMIN]));
        $group->delete('/blogs/{id}', [$contentController, 'deleteBlog'])
            ->add(new RoleMiddleware([User::ROLE_ADMIN, User::ROLE_WEB_ADMIN]));
            
    })->add($authMiddleware);
};
