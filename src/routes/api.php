<?php
return function ($app): void {
    // Define API routes here. This file is responsible for registering all API endpoints.
    // Get the request URI
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';

    // Map route prefixes to their router files
    // IMPORTANT: More specific prefixes MUST come before less specific ones
    
    $routeMap = [
        // Auth & Users
        '/v1/auth' => ROUTE . 'v1/AuthRoute.php',
        '/v1/users' => ROUTE . 'v1/UserRoute.php',
        '/v1/profile' => ROUTE . 'v1/ProfileRoute.php',

        // Public CMS Routes
        '/v1/hero-slides' => ROUTE . 'v1/HeroSlideRoute.php',
        '/v1/blog' => ROUTE . 'v1/BlogPostRoute.php',
        '/v1/events' => ROUTE . 'v1/EventRoute.php',
        '/v1/faqs' => ROUTE . 'v1/FAQRoute.php',
        '/v1/sectors' => ROUTE . 'v1/SectorRoute.php',
        '/v1/categories' => ROUTE . 'v1/CategoryRoute.php',
        '/v1/projects' => ROUTE . 'v1/ProjectRoute.php',
        '/v1/stats' => ROUTE . 'v1/CommunityStatRoute.php',
        '/v1/contact' => ROUTE . 'v1/ContactInfoRoute.php',
        '/v1/newsletter' => ROUTE . 'v1/NewsletterRoute.php',
        '/v1/issues' => ROUTE . 'v1/IssueReportRoute.php',
        '/v1/gallery' => ROUTE . 'v1/GalleryRoute.php',
        '/v1/locations' => ROUTE . 'v1/LocationRoute.php',
        '/v1/youth' => ROUTE . 'v1/YouthRegistrationRoute.php',

        // Role-specific dashboard routes (more specific first)
        '/v1/officer/dashboard' => ROUTE . 'v1/DashboardRoute.php',
        '/v1/officer/issues' => ROUTE . 'v1/IssueReportRoute.php',
        '/v1/officer/reports' => ROUTE . 'v1/OfficerReportsRoute.php',
        '/v1/officer' => ROUTE . 'v1/OfficerRoute.php',
        '/v1/agent/dashboard' => ROUTE . 'v1/DashboardRoute.php',
        '/v1/agent/issues' => ROUTE . 'v1/IssueReportRoute.php',
        '/v1/agent' => ROUTE . 'v1/AgentRoute.php',
        '/v1/task-force/dashboard' => ROUTE . 'v1/DashboardRoute.php',
        '/v1/task-force' => ROUTE . 'v1/TaskForceRoute.php',

        // Admin routes (more specific first)
        '/v1/admin/data' => ROUTE . 'v1/AdminDataRoute.php',
        '/v1/admin/dashboard' => ROUTE . 'v1/DashboardRoute.php',
        '/v1/admin/users' => ROUTE . 'v1/UserRoute.php',
        '/v1/admin/hero-slides' => ROUTE . 'v1/HeroSlideRoute.php',
        '/v1/admin/blog' => ROUTE . 'v1/BlogPostRoute.php',
        '/v1/admin/events' => ROUTE . 'v1/EventRoute.php',
        '/v1/admin/faqs' => ROUTE . 'v1/FAQRoute.php',
        '/v1/admin/sectors' => ROUTE . 'v1/SectorRoute.php',
        '/v1/admin/sub-sectors' => ROUTE . 'v1/SectorRoute.php',
        '/v1/admin/categories' => ROUTE . 'v1/CategoryRoute.php',
        '/v1/admin/projects' => ROUTE . 'v1/ProjectRoute.php',
        '/v1/admin/stats' => ROUTE . 'v1/CommunityStatRoute.php',
        '/v1/admin/contact' => ROUTE . 'v1/ContactInfoRoute.php',
        '/v1/admin/newsletter' => ROUTE . 'v1/NewsletterRoute.php',
        '/v1/admin/issues' => ROUTE . 'v1/IssueReportRoute.php',
        '/v1/admin/web-admins' => ROUTE . 'v1/WebAdminRoute.php',
        '/v1/admin/officers' => ROUTE . 'v1/OfficerRoute.php',
        '/v1/admin/agents' => ROUTE . 'v1/AgentRoute.php',
        '/v1/admin/task-force' => ROUTE . 'v1/TaskForceRoute.php',
        '/v1/admin/locations' => ROUTE . 'v1/LocationRoute.php',
        '/v1/admin/youth-programs' => ROUTE . 'v1/YouthProgramRoute.php',
        '/v1/admin/youth-records' => ROUTE . 'v1/YouthRecordRoute.php',
        '/v1/admin/notifications' => ROUTE . 'v1/NotificationRoute.php',
        '/v1/admin/audit-logs' => ROUTE . 'v1/AuditLogRoute.php',
        '/v1/admin/gallery' => ROUTE . 'v1/GalleryRoute.php',
        '/v1/admin/upload' => ROUTE . 'v1/UploadRoute.php',

        // New Dashboard Feature Routes
        '/v1/announcements' => ROUTE . 'v1/AnnouncementRoute.php',
        '/v1/admin/announcements' => ROUTE . 'v1/AnnouncementRoute.php',
        '/v1/jobs' => ROUTE . 'v1/EmploymentJobRoute.php',
        '/v1/ideas' => ROUTE . 'v1/CommunityIdeaRoute.php',
        '/v1/youth-programs' => ROUTE . 'v1/YouthProgramRoute.php',
        '/v1/notifications' => ROUTE . 'v1/NotificationRoute.php',
    ];

    $loadedFiles = [];
    $loaded = false;

    // Check if the request matches any of our defined prefixes
    foreach ($routeMap as $prefix => $routerFile) {
        if (strpos($requestUri, $prefix) === 0) {
            // Load only the matching router
            if (file_exists($routerFile) && !in_array($routerFile, $loadedFiles)) {
                $routeLoader = require $routerFile;
                if (is_callable($routeLoader)) {
                    $routeLoader($app);
                    $loadedFiles[] = $routerFile;
                    $loaded = true;
                }
            }
        }
    }

    // If no specific router was loaded, load all routers as fallback
    if (!$loaded) {
        foreach ($routeMap as $routerFile) {
            if (file_exists($routerFile) && !in_array($routerFile, $loadedFiles)) {
                $routeLoader = require $routerFile;
                if (is_callable($routeLoader)) {
                    $routeLoader($app);
                    $loadedFiles[] = $routerFile;
                }
            }
        }
    }
};
