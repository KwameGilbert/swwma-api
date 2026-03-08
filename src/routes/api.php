<?php
return function ($app): void {
    // Define API routes here. This file is responsible for registering all API endpoints.
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';

    /**
     * Route Map
     * Maps path prefixes to their respective route loader files.
     */
    $routeMap = [
        // Auth & Users
        '/v1/auth' => ROUTE . 'v1/AuthRoute.php',
        '/v1/users' => ROUTE . 'v1/UserRoute.php',
        
        // Lookup Data
        '/v1/locations' => ROUTE . 'v1/LocationRoute.php',
        '/v1/categories' => ROUTE . 'v1/CategoryRoute.php',
        '/v1/sectors' => ROUTE . 'v1/SectorRoute.php',
        '/v1/sub-sectors' => ROUTE . 'v1/SubSectorRoute.php',
        '/v1/constituents' => ROUTE . 'v1/ConstituentRoute.php',
        '/v1/issues' => ROUTE . 'v1/IssueRoute.php',
        
        // Content & Community
        '/v1/community-ideas' => ROUTE . 'v1/ContentRoute.php',
        '/v1/blog-posts' => ROUTE . 'v1/ContentRoute.php',
        '/v1/content' => ROUTE . 'v1/ContentRoute.php',

        // Development & Projects
        '/v1/projects' => ROUTE . 'v1/DevelopmentRoute.php',
        '/v1/events' => ROUTE . 'v1/DevelopmentRoute.php',
        '/v1/development' => ROUTE . 'v1/DevelopmentRoute.php',

        // Employment
        '/v1/jobs' => ROUTE . 'v1/EmploymentRoute.php',
        '/v1/job-applicants' => ROUTE . 'v1/EmploymentRoute.php',
        '/v1/employment' => ROUTE . 'v1/EmploymentRoute.php',
        '/v1/agent' => ROUTE . 'v1/AgentRoute.php',
        '/v1/officer' => ROUTE . 'v1/OfficerRoute.php',

        // System
        '/v1/utils' => ROUTE . 'v1/UtilsRoute.php',
    ];

    $loadedFiles = [];
    $loaded = false;

    // Direct routing for efficiency based on prefix
    foreach ($routeMap as $prefix => $routerFile) {
        if (strpos($requestUri, $prefix) === 0) {
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

    // Fallback if the path didn't match a specific prefix (e.g., base path or misc)
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
