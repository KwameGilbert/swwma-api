<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Sector;
use App\Helper\ResponseHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\ActivityLogService;
use Exception;

class SectorController
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
            $query = Sector::with(['category', 'subsectors']);

            if (isset($params['category_id'])) {
                $query->where('category_id', $params['category_id']);
            }

            $sectors = $query->get();
            return ResponseHelper::success($response, 'Sectors fetched successfully', ['sectors' => $sectors->toArray()]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch sectors', 500, $e->getMessage());
        }
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $sector = Sector::with(['category', 'subsectors'])->find($args['id']);
            if (!$sector) {
                return ResponseHelper::error($response, 'Sector not found', 404);
            }
            return ResponseHelper::success($response, 'Sector fetched successfully', ['sector' => $sector->toArray()]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch sector', 500, $e->getMessage());
        }
    }

    public function create(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            if (empty($data['name']) || empty($data['category_id'])) {
                return ResponseHelper::error($response, 'Name and category_id are required', 400);
            }
            $sector = Sector::create($data);

            $this->activityLogger->logCreate(
                $request->getAttribute('user')->id ?? null,
                'Sector',
                (int)$sector->id,
                $sector->toArray()
            );

            return ResponseHelper::success($response, 'Sector created successfully', $sector->toArray(), 201);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to create sector', 500, $e->getMessage());
        }
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $sector = Sector::find($args['id']);
            if (!$sector) {
                return ResponseHelper::error($response, 'Sector not found', 404);
            }
            $data = $request->getParsedBody();
            $oldValues = $sector->toArray();
            $sector->update($data);

            $this->activityLogger->logUpdate(
                $request->getAttribute('user')->id ?? null,
                'Sector',
                (int)$sector->id,
                $oldValues,
                $sector->toArray()
            );

            return ResponseHelper::success($response, 'Sector updated successfully', ['sector' => $sector->toArray()]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to update sector', 500, $e->getMessage());
        }
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $sector = Sector::find($args['id']);
            if (!$sector) {
                return ResponseHelper::error($response, 'Sector not found', 404);
            }
            if ($sector->subsectors()->count() > 0) {
                return ResponseHelper::error($response, 'Cannot delete sector with associated subsectors', 400);
            }

            $oldValues = $sector->toArray();
            $sectorId = (int)$sector->id;
            $sector->delete();

            $this->activityLogger->logDelete(
                $request->getAttribute('user')->id ?? null,
                'Sector',
                $sectorId,
                $oldValues
            );

            return ResponseHelper::success($response, 'Sector deleted successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to delete sector', 500, $e->getMessage());
        }
    }
}
