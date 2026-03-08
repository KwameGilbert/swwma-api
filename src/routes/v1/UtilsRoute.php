<?php

/**
 * Utility Routes (v1 API)
 * Provides utility endpoints like image-to-base64 conversion
 */

use Slim\App;

return function (App $app): void {
    // Public utility routes (no auth required)
    $app->group('/v1/utils', function ($group) {
        // Reserved for future utility functions like image-to-base64 
    });
};
