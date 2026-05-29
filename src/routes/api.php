<?php
return function ($app): void {
    // White-list of active routing files for the Constituency Development Hub
    $activeRoutes = [
        ROUTE . 'v1/AdminRoute.php',
        ROUTE . 'v1/AgentRoute.php',
        ROUTE . 'v1/AuthRoute.php',
        ROUTE . 'v1/CategoryRoute.php',
        ROUTE . 'v1/ConstituentRoute.php',
        ROUTE . 'v1/ContentRoute.php',
        ROUTE . 'v1/DevelopmentRoute.php',
        ROUTE . 'v1/EmploymentRoute.php',
        ROUTE . 'v1/IssueRoute.php',
        ROUTE . 'v1/LocationRoute.php',
        ROUTE . 'v1/OfficerRoute.php',
        ROUTE . 'v1/SectorRoute.php',
        ROUTE . 'v1/SubSectorRoute.php',
        ROUTE . 'v1/UserRoute.php',
        ROUTE . 'v1/UtilsRoute.php',
    ];

    foreach ($activeRoutes as $file) {
        if (file_exists($file)) {
            $routeLoader = require $file;
            if (is_callable($routeLoader)) {
                $routeLoader($app);
            }
        }
    }
};
