<?php
return function ($app): void {
    // Dynamically load all versioned route files in the v1 directory
    $routeDir = ROUTE . 'v1/';
    $routeFiles = glob($routeDir . '*.php');
    
    if (is_array($routeFiles)) {
        foreach ($routeFiles as $file) {
            if (file_exists($file)) {
                $routeLoader = require $file;
                if (is_callable($routeLoader)) {
                    $routeLoader($app);
                }
            }
        }
    }
};
