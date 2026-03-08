<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Issue;
use App\Models\IssueAssessment;
use App\Helper\ResponseHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\ActivityLogService;
use Exception;

/**
 * IssueAssessmentController
 * Handles the technical assessment reports for reported issues.
 */
class IssueAssessmentController
{
    private ActivityLogService $activityLogger;

    public function __construct(ActivityLogService $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    /**
     * Create or update an assessment for an issue
     * POST /v1/issues/{id}/assessment
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
            if (empty($data['description'])) {
                return ResponseHelper::error($response, 'Assessment description is required', 400);
            }

            $assessment = IssueAssessment::updateOrCreate(
                ['issues_id' => $issueId],
                [
                    'recommendations' => $data['recommendations'] ?? null,
                    'estimated_costs' => $data['estimated_costs'] ?? 0,
                    'estimated_duration' => $data['estimated_duration'] ?? null,
                    'description' => $data['description'],
                    'issue_confirmed' => $data['issue_confirmed'] ?? true,
                    'attachments' => $data['attachments'] ?? [],
                    'status' => $data['status'] ?? IssueAssessment::STATUS_PENDING_APPROVAL
                ]
            );

            // Automatically update the issue status to 'assessment_submitted'
            $issue->status = Issue::STATUS_ASSESSMENT_SUBMITTED;
            $issue->save();

            $this->activityLogger->logUpdate(
                $request->getAttribute('user')->id ?? null,
                'Issue Assessment',
                $issueId,
                [],
                $assessment->toArray()
            );

            return ResponseHelper::success($response, 'Assessment saved successfully', $assessment->toArray(), 201);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to save assessment', 500, $e->getMessage());
        }
    }

    /**
     * Get assessment for a specific issue
     * GET /v1/issues/{id}/assessment
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $assessment = IssueAssessment::where('issues_id', $args['id'])->first();
            
            if (!$assessment) {
                return ResponseHelper::error($response, 'No assessment found for this issue', 404);
            }

            return ResponseHelper::success($response, 'Assessment fetched successfully', $assessment->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch assessment', 500, $e->getMessage());
        }
    }

    /**
     * Approve or reject an assessment
     * PATCH /v1/issues/{id}/assessment/status
     */
    public function updateStatus(Request $request, Response $response, array $args): Response
    {
        try {
            $assessment = IssueAssessment::where('issues_id', $args['id'])->first();
            if (!$assessment) {
                return ResponseHelper::error($response, 'Assessment not found', 404);
            }

            $data = $request->getParsedBody();
            if (empty($data['status'])) {
                return ResponseHelper::error($response, 'Status is required', 400);
            }

            $oldStatus = $assessment->status;
            $assessment->status = $data['status'];
            $assessment->save();

            // If approved, we might want to advance the issue status
            if ($assessment->status === IssueAssessment::STATUS_APPROVED) {
                $issue = Issue::find($args['id']);
                $issue->status = Issue::STATUS_RESOLUTION_IN_PROGRESS;
                $issue->save();
            }

            $this->activityLogger->logUpdate(
                $request->getAttribute('user')->id ?? null,
                'Assessment Status Update',
                (int)$assessment->id,
                ['status' => $oldStatus],
                ['status' => $assessment->status]
            );

            return ResponseHelper::success($response, 'Assessment status updated successfully', $assessment->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to update assessment status', 500, $e->getMessage());
        }
    }
}
