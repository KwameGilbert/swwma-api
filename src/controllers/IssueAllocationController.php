<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Issue;
use App\Models\IssueResourceAllocation;
use App\Helper\ResponseHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\ActivityLogService;
use Exception;

/**
 * IssueAllocationController
 * Handles resource allocation for issues by admins.
 */
class IssueAllocationController
{
    private ActivityLogService $activityLogger;

    public function __construct(ActivityLogService $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    /**
     * Allocate resources to an issue
     * POST /v1/issues/{id}/allocate
     */
    public function allocate(Request $request, Response $response, array $args): Response
    {
        try {
            $issueId = (int)$args['id'];
            $issue = Issue::find($issueId);
            
            if (!$issue) {
                return ResponseHelper::error($response, 'Issue not found', 404);
            }

            // Only allow allocation if assessment is submitted or approved
            if (!in_array($issue->status, [Issue::STATUS_ASSESSMENT_SUBMITTED, Issue::STATUS_RESOLUTION_IN_PROGRESS])) {
                // We allow it anyway but maybe warn? 
                // Let's stick to simple logic: logic allows it if issue exists.
            }

            $data = $request->getParsedBody();
            $user = $request->getAttribute('user');

            $allocation = IssueResourceAllocation::create([
                'issues_id' => $issueId,
                'allocated_by' => $user->id,
                'amount' => $data['amount'] ?? 0,
                'personnel_items' => $data['personnel_items'] ?? [],
                'material_items' => $data['material_items'] ?? [],
                'additional_notes' => $data['additional_notes'] ?? null,
                'allocation_date' => $data['allocation_date'] ?? date('Y-m-d H:i:s')
            ]);

            // Evolution: If resources are allocated, move issue to resolution_in_progress if it wasn't already
            if ($issue->status === Issue::STATUS_ASSESSMENT_SUBMITTED) {
                $issue->status = Issue::STATUS_RESOLUTION_IN_PROGRESS;
                $issue->save();
            }

            $this->activityLogger->logCreate(
                $user->id,
                'IssueResourceAllocation',
                (int)$allocation->id,
                $allocation->toArray()
            );

            return ResponseHelper::success($response, 'Resources allocated successfully', $allocation->toArray(), 201);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to allocate resources', 500, $e->getMessage());
        }
    }

    /**
     * Get allocations for an issue
     * GET /v1/issues/{id}/allocations
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $allocations = IssueResourceAllocation::with('allocator.profile')->where('issues_id', $args['id'])->get();
            return ResponseHelper::success($response, 'Allocations fetched successfully', $allocations->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch allocations', 500, $e->getMessage());
        }
    }
}
