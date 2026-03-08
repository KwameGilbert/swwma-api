<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Job;
use App\Models\JobApplicant;
use App\Helper\ResponseHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\ActivityLogService;
use Exception;

/**
 * EmploymentController
 * Handles job management and job applications.
 */
class EmploymentController
{
    private ActivityLogService $activityLogger;

    public function __construct(ActivityLogService $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    // --- JOB LISTINGS MANAGEMENT ---

    /**
     * List all jobs with optional filtering
     * GET /v1/jobs
     */
    public function listJobs(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $query = Job::query();
            
            // Public filter: open only
            if (!empty($params['open_only'])) {
                $query->open();
            }

            if (!empty($params['status'])) {
                $query->where('status', $params['status']);
            }

            $jobs = $query->latest()->get();
            return ResponseHelper::success($response, 'Jobs fetched successfully', $jobs->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch jobs', 500);
        }
    }

    /**
     * Get single job details
     * GET /v1/jobs/{id}
     */
    public function showJob(Request $request, Response $response, array $args): Response
    {
        try {
            $job = Job::find($args['id']);
            if (!$job) return ResponseHelper::error($response, 'Job not found', 404);
            return ResponseHelper::success($response, 'Job fetched successfully', $job->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch job details', 500);
        }
    }

    /**
     * Create a new job listing
     * POST /v1/jobs
     */
    public function createJob(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            if (empty($data['title']) || empty($data['description'])) {
                return ResponseHelper::error($response, 'Title and description are required', 400);
            }
            $job = Job::create($data);

            $this->activityLogger->logCreate(
                $request->getAttribute('user')->id ?? null,
                'Job',
                (int)$job->id,
                $job->toArray()
            );

            return ResponseHelper::success($response, 'Job created successfully', $job->toArray(), 201);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to create job', 500);
        }
    }

    /**
     * Update an existing job listing
     * PUT /v1/jobs/{id}
     */
    public function updateJob(Request $request, Response $response, array $args): Response
    {
        try {
            $job = Job::find($args['id']);
            if (!$job) return ResponseHelper::error($response, 'Job not found', 404);

            $data = $request->getParsedBody();
            $oldValues = $job->toArray();
            $job->update($data);

            $this->activityLogger->logUpdate(
                $request->getAttribute('user')->id ?? null,
                'Job',
                (int)$job->id,
                $oldValues,
                $job->toArray()
            );

            return ResponseHelper::success($response, 'Job updated successfully', $job->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to update job', 500);
        }
    }

    // --- JOB APPLICATIONS ---

    /**
     * Submit a job application
     * POST /v1/jobs/{id}/apply
     */
    public function apply(Request $request, Response $response, array $args): Response
    {
        try {
            $jobId = (int)$args['id'];
            $job = Job::find($jobId);
            
            if (!$job || $job->status !== Job::STATUS_OPEN) {
                return ResponseHelper::error($response, 'Job not found or is closed for applications', 404);
            }

            $data = $request->getParsedBody();
            
            // Basic validation
            if (empty($data['first_name']) || empty($data['last_name']) || empty($data['phone'])) {
                return ResponseHelper::error($response, 'First name, last name and phone number are required', 400);
            }

            $application = JobApplicant::create(array_merge($data, [
                'job_id' => $jobId,
                'status' => JobApplicant::STATUS_PENDING
            ]));

            return ResponseHelper::success($response, 'Application submitted successfully', $application->toArray(), 201);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to submit application', 500, $e->getMessage());
        }
    }

    /**
     * List all applicants with optional filtering by job
     * GET /v1/job-applicants
     */
    public function listApplicants(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $query = JobApplicant::with('job');
            
            if (!empty($params['job_id'])) {
                $query->where('job_id', $params['job_id']);
            }

            if (!empty($params['status'])) {
                $query->where('status', $params['status']);
            }

            $applicants = $query->latest()->get();
            return ResponseHelper::success($response, 'Applicants fetched successfully', $applicants->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch applicants', 500);
        }
    }

    /**
     * Update applicant status
     * PATCH /v1/job-applicants/{id}/status
     */
    public function updateApplicantStatus(Request $request, Response $response, array $args): Response
    {
        try {
            $applicant = JobApplicant::find($args['id']);
            if (!$applicant) return ResponseHelper::error($response, 'Applicant not found', 404);

            $data = $request->getParsedBody();
            if (empty($data['status'])) {
                return ResponseHelper::error($response, 'Status is required', 400);
            }

            $oldValues = $applicant->toArray();
            $applicant->status = $data['status'];
            $applicant->save();

            $this->activityLogger->logUpdate(
                $request->getAttribute('user')->id ?? null,
                'JobApplicant Status',
                (int)$applicant->id,
                ['status' => $oldValues['status']],
                ['status' => $applicant->status]
            );

            return ResponseHelper::success($response, 'Applicant status updated successfully', $applicant->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to update applicant status', 500);
        }
    }
}
