<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Location;
use App\Helper\ResponseHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\ActivityLogService;
use Exception;
use Respect\Validation\Validator as v;

/**
 * LocationController
 * Handles management of Communities and Suburbs.
 */
class LocationController
{
    private ActivityLogService $activityLogger;

    public function __construct(ActivityLogService $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    /**
     * Get all locations (Communities and Suburbs)
     * GET /v1/locations
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $query = Location::with('children');

            // Filter by type if provided
            if (isset($params['type'])) {
                $query->byType($params['type']);
            }

            // Only get root locations (communities) if specifically requested
            if (isset($params['roots_only']) && $params['roots_only'] === 'true') {
                $query->whereNull('parent_id');
            }

            $locations = $query->get();

            return ResponseHelper::success($response, 'Locations fetched successfully', [
                'locations' => $locations,
                'count' => $locations->count()
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch locations', 500, $e->getMessage());
        }
    }

    /**
     * Get single location details
     * GET /v1/locations/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $location = Location::with(['parent', 'children'])->find($args['id']);

            if (!$location) {
                return ResponseHelper::error($response, 'Location not found', 404);
            }

            return ResponseHelper::success($response, 'Location fetched successfully', ['location' => $location->toArray()]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch location', 500, $e->getMessage());
        }
    }

    /**
     * Create new location
     * POST /v1/locations
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();

            // Validation
            if (empty($data['name']) || empty($data['type'])) {
                return ResponseHelper::error($response, 'Name and type are required', 400);
            }

            if (!in_array($data['type'], [Location::TYPE_COMMUNITY, Location::TYPE_SUBURB])) {
                return ResponseHelper::error($response, 'Invalid type', 400);
            }

            // If type is suburb, parent_id is usually required
            if ($data['type'] === Location::TYPE_SUBURB && empty($data['parent_id'])) {
                return ResponseHelper::error($response, 'Parent community id is required for suburbs', 400);
            }

            $location = Location::create([
                'name' => $data['name'],
                'type' => $data['type'],
                'parent_id' => $data['parent_id'] ?? null
            ]);

            return ResponseHelper::success($response, 'Location created successfully', ['location' => $location->toArray()], 201);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to create location', 500, $e->getMessage());
        }
    }

    /**
     * Update location
     * PUT /v1/locations/{id}
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $location = Location::find($args['id']);
            if (!$location) {
                return ResponseHelper::error($response, 'Location not found', 404);
            }

            $data = $request->getParsedBody();
            
            // Filter only fillable fields
            $updates = array_intersect_key($data, array_flip(['name', 'type', 'parent_id']));
            
            $location->update($updates);

            return ResponseHelper::success($response, 'Location updated successfully', ['location' => $location->toArray()]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to update location', 500, $e->getMessage());
        }
    }

    /**
     * Delete location
     * DELETE /v1/locations/{id}
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $location = Location::find($args['id']);
            if (!$location) {
                return ResponseHelper::error($response, 'Location not found', 404);
            }

            // Check if it has children before deleting
            if ($location->children()->count() > 0) {
                return ResponseHelper::error($response, 'Cannot delete location that has children (suburbs)', 400);
            }

            $location->delete();
            return ResponseHelper::success($response, 'Location deleted successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to delete location', 500, $e->getMessage());
        }
    }
}
