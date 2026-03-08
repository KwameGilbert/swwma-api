<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Issue;
use App\Models\IssueResolution;
use App\Helper\ResponseHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\ActivityLogService;
use Exception;

/**
 * IssueResolutionController
 * Handles the final resolution reports for issues.
 */
class IssueResolutionController
{
    private ActivityLogService $activityLogger;

    public function __construct(ActivityLogService $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    /**
     * Create or update a resolution for an issue
     * POST /v1/issues/{id}/resolution
     */
    public function createOrUpdate(Request $request, Response $response, array $args): Response
    {
        try {
            $issueId = (int)$args['id'];
            $issue = Issue::find($issueId);
            
            if (!$issue) {
                return ResponseHelper::error($response, 'Issue not found', 404);
            }

            $data = $request->getParsedBody();
            
            // Basic validation
            if (empty($data['summary'])) {
                return ResponseHelper::error($response, 'Resolution summary is required', 400);
            }

            $resolution = IssueResolution::updateOrCreate(
                ['issues_id' => $issueId],
                [
                    'summary' => $data['summary'],
                    'status' => $data['status'] ?? IssueResolution::STATUS_SUBMITTED
                ]
            );

            // If status is completed, update the parent issue to 'resolved'
            if ($resolution->status === IssueResolution::STATUS_COMPLETED) {
                $issue->status = Issue::STATUS_RESOLVED;
                $issue->save();
            }

            $this->activityLogger->logUpdate(
                $request->getAttribute('user')->id ?? null,
                'Issue Resolution',
                $issueId,
                [],
                $resolution->toArray()
            );

            return ResponseHelper::success($response, 'Resolution saved successfully', $resolution->toArray(), 201);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to save resolution', 500, $e->getMessage());
        }
    }

    /**
     * Get resolution for a specific issue
     * GET /v1/issues/{id}/resolution
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $resolution = IssueResolution::where('issues_id', $args['id'])->first();
            
            if (!$resolution) {
                return ResponseHelper::error($response, 'No resolution report found for this issue', 404);
            }

            return ResponseHelper::success($response, 'Resolution fetched successfully', $resolution->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch resolution', 500, $e->getMessage());
        }
    }
}
