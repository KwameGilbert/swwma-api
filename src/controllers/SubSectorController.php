<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\SubSector;
use App\Helper\ResponseHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\ActivityLogService;
use Exception;

class SubSectorController
{
    private ActivityLogService $activityLogger;

    public function __construct(ActivityLogService $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    public function index(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $query = SubSector::with('sector.category');

            if (isset($params['sector_id'])) {
                $query->where('sector_id', $params['sector_id']);
            }

            $subsectors = $query->get();
            return ResponseHelper::success($response, 'Sub-sectors fetched successfully', $subsectors->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch sub-sectors', 500, $e->getMessage());
        }
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $subsector = SubSector::with('sector.category')->find($args['id']);
            if (!$subsector) {
                return ResponseHelper::error($response, 'Sub-sector not found', 404);
            }
            return ResponseHelper::success($response, 'Sub-sector fetched successfully', $subsector->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch sub-sector', 500, $e->getMessage());
        }
    }

    public function create(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            if (empty($data['name']) || empty($data['sector_id'])) {
                return ResponseHelper::error($response, 'Name and sector_id are required', 400);
            }
            $subsector = SubSector::create($data);

            $this->activityLogger->logCreate(
                $request->getAttribute('user')->id ?? null,
                'SubSector',
                (int)$subsector->id,
                $subsector->toArray()
            );

            return ResponseHelper::success($response, 'Sub-sector created successfully', $subsector->toArray(), 201);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to create sub-sector', 500, $e->getMessage());
        }
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $subsector = SubSector::find($args['id']);
            if (!$subsector) {
                return ResponseHelper::error($response, 'Sub-sector not found', 404);
            }
            $data = $request->getParsedBody();
            $oldValues = $subsector->toArray();
            $subsector->update($data);

            $this->activityLogger->logUpdate(
                $request->getAttribute('user')->id ?? null,
                'SubSector',
                (int)$subsector->id,
                $oldValues,
                $subsector->toArray()
            );

            return ResponseHelper::success($response, 'Sub-sector updated successfully', $subsector->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to update sub-sector', 500, $e->getMessage());
        }
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $subsector = SubSector::find($args['id']);
            if (!$subsector) {
                return ResponseHelper::error($response, 'Sub-sector not found', 404);
            }
            $oldValues = $subsector->toArray();
            $subsectorId = (int)$subsector->id;
            $subsector->delete();

            $this->activityLogger->logDelete(
                $request->getAttribute('user')->id ?? null,
                'SubSector',
                $subsectorId,
                $oldValues
            );

            return ResponseHelper::success($response, 'Sub-sector deleted successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to delete sub-sector', 500, $e->getMessage());
        }
    }
}
