<?php

/**
 * Service Container Registration
 * 
 * Registers all services, controllers, and middleware with the DI container
 */

use App\Services\EmailService;
use App\Services\SMSService;
use App\Services\AuthService;
use App\Services\ActivityLogService;
use App\Services\UploadService;
use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Controllers\LocationController;
use App\Controllers\CategoryController;
use App\Controllers\SectorController;
use App\Controllers\SubSectorController;
use App\Controllers\ConstituentController;
use App\Controllers\IssueController;
use App\Controllers\IssueAssessmentController;
use App\Controllers\IssueResolutionController;
use App\Controllers\IssueAllocationController;
use App\Controllers\ContentManagementController;
use App\Controllers\DevelopmentManagementController;
use App\Controllers\EmploymentController;
use App\Controllers\PasswordResetController;
use App\Controllers\AgentDashboardController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\JsonBodyParserMiddleware;

return function ($container) {
    
    // ==================== SERVICES ====================
    
    $container->set(EmailService::class, function () {
        return new EmailService();
    });

    $container->set(SMSService::class, function () {
        return new SMSService();
    });
    
    $container->set(AuthService::class, function () {
        return new AuthService();
    });
    
    $container->set(ActivityLogService::class, function () {
        return new ActivityLogService();
    });

    $container->set(UploadService::class, function () {
        return new UploadService();
    });

    $container->set(\Psr\Http\Message\ResponseFactoryInterface::class, function () {
        return new \Slim\Psr7\Factory\ResponseFactory();
    });
    
    // ==================== CONTROLLERS ====================
    
    $container->set(AuthController::class, function ($container) {
        return new AuthController(
            $container->get(AuthService::class),
            $container->get(ActivityLogService::class)
        );
    });
    
    $container->set(UserController::class, function ($container) {
        return new UserController($container->get(ActivityLogService::class));
    });

    $container->set(LocationController::class, function ($container) {
        return new LocationController($container->get(ActivityLogService::class));
    });

    $container->set(CategoryController::class, function ($container) {
        return new CategoryController($container->get(ActivityLogService::class));
    });

    $container->set(SectorController::class, function ($container) {
        return new SectorController($container->get(ActivityLogService::class));
    });

    $container->set(SubSectorController::class, function ($container) {
        return new SubSectorController($container->get(ActivityLogService::class));
    });

    $container->set(ConstituentController::class, function ($container) {
        return new ConstituentController($container->get(ActivityLogService::class));
    });

    $container->set(IssueController::class, function ($container) {
        return new IssueController(
            $container->get(ActivityLogService::class),
            $container->get(UploadService::class)
        );
    });

    $container->set(IssueAssessmentController::class, function ($container) {
        return new IssueAssessmentController($container->get(ActivityLogService::class));
    });

    $container->set(IssueResolutionController::class, function ($container) {
        return new IssueResolutionController($container->get(ActivityLogService::class));
    });

    $container->set(IssueAllocationController::class, function ($container) {
        return new IssueAllocationController($container->get(ActivityLogService::class));
    });

    $container->set(ContentManagementController::class, function ($container) {
        return new ContentManagementController($container->get(ActivityLogService::class));
    });

    $container->set(DevelopmentManagementController::class, function ($container) {
        return new DevelopmentManagementController($container->get(ActivityLogService::class));
    });

    $container->set(EmploymentController::class, function ($container) {
        return new EmploymentController($container->get(ActivityLogService::class));
    });

    $container->set(AgentDashboardController::class, function () {
        return new AgentDashboardController();
    });
    
    $container->set(PasswordResetController::class, function ($container) {
        return new PasswordResetController(
            $container->get(AuthService::class),
            $container->get(EmailService::class)
        );
    });
    
    // ==================== MIDDLEWARES ====================
    
    $container->set(AuthMiddleware::class, function ($container) {
        return new AuthMiddleware($container->get(AuthService::class));
    });
    
    $container->set(RateLimitMiddleware::class, function () {
        return new RateLimitMiddleware();
    });
    
    $container->set(JsonBodyParserMiddleware::class, function () {
        return new JsonBodyParserMiddleware();
    });

    return $container;
};
