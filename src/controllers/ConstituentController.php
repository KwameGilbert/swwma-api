<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Constituent;
use App\Helper\ResponseHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\ActivityLogService;
use Exception;

/**
 * ConstituentController
 * Handles management of constituency members.
 */
class ConstituentController
{
    private ActivityLogService $activityLogger;

    public function __construct(ActivityLogService $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    /**
     * List constituents with optional filtering
     * GET /v1/constituents
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $query = Constituent::query();

            // Search by name or phone
            if (!empty($params['search'])) {
                $search = $params['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('phone_number', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Filter by gender
            if (!empty($params['gender'])) {
                $query->where('gender', $params['gender']);
            }

            $constituents = $query->latest()->get();

            return ResponseHelper::success($response, 'Constituents fetched successfully', $constituents->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch constituents', 500, $e->getMessage());
        }
    }

    /**
     * Get single constituent
     * GET /v1/constituents/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $constituent = Constituent::with('issues')->find($args['id']);

            if (!$constituent) {
                return ResponseHelper::error($response, 'Constituent not found', 404);
            }

            return ResponseHelper::success($response, 'Constituent fetched successfully', $constituent->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch constituent', 500, $e->getMessage());
        }
    }

    /**
     * Create new constituent
     * POST /v1/constituents
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();

            // Basic validation
            if (empty($data['name']) || empty($data['phone_number'])) {
                return ResponseHelper::error($response, 'Name and phone number are required', 400);
            }

            // Check if phone number already exists (optional, depends on use case)
            $existing = Constituent::where('phone_number', $data['phone_number'])->first();
            if ($existing) {
                return ResponseHelper::success($response, 'Constituent already exists', $existing->toArray());
            }

            $constituent = Constituent::create($data);

            $this->activityLogger->logCreate(
                $request->getAttribute('user')->id ?? null,
                'Constituent',
                (int)$constituent->id,
                $constituent->toArray()
            );

            return ResponseHelper::success($response, 'Constituent registered successfully', $constituent->toArray(), 201);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to register constituent', 500, $e->getMessage());
        }
    }

    /**
     * Update constituent
     * PUT /v1/constituents/{id}
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $constituent = Constituent::find($args['id']);
            if (!$constituent) {
                return ResponseHelper::error($response, 'Constituent not found', 404);
            }

            $data = $request->getParsedBody();
            $oldValues = $constituent->toArray();
            
            $constituent->update($data);

            $this->activityLogger->logUpdate(
                $request->getAttribute('user')->id ?? null,
                'Constituent',
                (int)$constituent->id,
                $oldValues,
                $constituent->toArray()
            );

            return ResponseHelper::success($response, 'Constituent updated successfully', $constituent->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to update constituent', 500, $e->getMessage());
        }
    }

    /**
     * Delete constituent
     * DELETE /v1/constituents/{id}
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $constituent = Constituent::find($args['id']);
            if (!$constituent) {
                return ResponseHelper::error($response, 'Constituent not found', 404);
            }

            // Check if they have issues before deleting (integrity)
            if ($constituent->issues()->count() > 0) {
                return ResponseHelper::error($response, 'Cannot delete constituent with active issue reports', 400);
            }

            $oldValues = $constituent->toArray();
            $constituentId = (int)$constituent->id;
            
            $constituent->delete();

            $this->activityLogger->logDelete(
                $request->getAttribute('user')->id ?? null,
                'Constituent',
                $constituentId,
                $oldValues
            );

            return ResponseHelper::success($response, 'Constituent deleted successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to delete constituent', 500, $e->getMessage());
        }
    }
}
