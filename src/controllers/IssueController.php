<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Issue;
use App\Models\Constituent;
use App\Models\User;
use App\Helper\ResponseHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\ActivityLogService;
use App\Services\UploadService;
use Exception;

/**
 * IssueController
 * Handles reporting and tracking of constituency issues.
 */
class IssueController
{
    private ActivityLogService $activityLogger;
    private UploadService $uploadService;

    public function __construct(ActivityLogService $activityLogger, UploadService $uploadService)
    {
        $this->activityLogger = $activityLogger;
        $this->uploadService = $uploadService;
    }

    /**
     * List all issues with comprehensive filtering
     * GET /v1/issues
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $query = Issue::with([
                'constituent', 
                'category', 
                'sector', 
                'subsector', 
                'community', 
                'suburb'
            ]);

            // Filter by Status
            if (!empty($params['status'])) {
                $query->where('status', $params['status']);
            }

            // Filter by Location
            if (!empty($params['community_id'])) {
                $query->where('community_id', $params['community_id']);
            }

            // Filter by Classification
            if (!empty($params['category_id'])) {
                $query->where('category_id', $params['category_id']);
            }

            // Search by title or description
            if (!empty($params['search'])) {
                $search = $params['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $issues = $query->latest()->get();

            return ResponseHelper::success($response, 'Issues fetched successfully', $issues->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch issues', 500, $e->getMessage());
        }
    }

    /**
     * Get single issue details
     * GET /v1/issues/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $issue = Issue::with([
                'constituent', 
                'category', 
                'sector', 
                'subsector', 
                'community', 
                'suburb'
            ])->find($args['id']);

            if (!$issue) {
                return ResponseHelper::error($response, 'Issue not found', 404);
            }

            return ResponseHelper::success($response, 'Issue details fetched successfully', $issue->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch issue details', 500, $e->getMessage());
        }
    }

    /**
     * Create a new issue report
     * Supports on-the-fly constituent creation or linking via constituent_id
     * POST /v1/issues
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();

            // 1. Handle Constituent Logic
            $constituentId = $data['constituent_id'] ?? null;

            // If constituent_id is missing, try to find or create by details
            if (!$constituentId) {
                if (empty($data['constituent_name']) || empty($data['constituent_phone'])) {
                    return ResponseHelper::error($response, "Either 'constituent_id' or constituent details ('constituent_name' & 'constituent_phone') are required", 400);
                }

                $constituent = Constituent::firstOrCreate(
                    ['phone_number' => $data['constituent_phone']],
                    [
                        'name' => $data['constituent_name'],
                        'email' => $data['constituent_email'] ?? null,
                        'gender' => $data['constituent_gender'] ?? null,
                        'home_address' => $data['constituent_address'] ?? null,
                    ]
                );
                $constituentId = (int)$constituent->id;
            }

            // 2. Validation for Issue Fields
            $required = ['title', 'description', 'category_id', 'sector_id', 'community_id'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ResponseHelper::error($response, "Field '{$field}' is required", 400);
                }
            }

            // 2. Handle Image Uploads
            $uploadedFiles = $request->getUploadedFiles();
            $images = [];
            if (!empty($uploadedFiles['images'])) {
                $images = $this->uploadService->uploadMultipleFiles($uploadedFiles['images'], 'image', 'issues');
            }

            // 3. Create the Issue
            $authUser = $request->getAttribute('user');
            $issueData = [
                'title' => $data['title'],
                'description' => $data['description'],
                'category_id' => $data['category_id'],
                'sector_id' => $data['sector_id'],
                'sub_sector_id' => $data['sub_sector_id'],
                'community_id' => $data['community_id'],
                'suburb_id' => $data['suburb_id'] ?? null,
                'specific_location' => $data['specific_location'] ?? null,
                'details' => $data['details'] ?? $data['additional_notes'] ?? null,
                'status' => $data['status'] ?? Issue::STATUS_SUBMITTED,
                'priority' => $data['priority'] ?? 'medium',
                'images' => $images,
                'constituent_id' => $constituentId
            ];

            if ($authUser->role === User::ROLE_AGENT) {
                $issueData['agent_id'] = $authUser->id;
            } elseif ($authUser->role === User::ROLE_OFFICER || $authUser->role === User::ROLE_ADMIN) {
                $issueData['officer_id'] = $authUser->id;
                if (!empty($data['agent_id'])) {
                    $issueData['agent_id'] = $data['agent_id'];
                }
            }

            $issue = Issue::create($issueData);

            $this->activityLogger->logCreate(
                $request->getAttribute('user')->id ?? null,
                'Issue',
                (int)$issue->id,
                $issue->toArray()
            );

            return ResponseHelper::success($response, 'Issue reported successfully', [
                'report' => $issue->load('constituent')->toArray()
            ], 201);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to report issue', 500, $e->getMessage());
        }
    }

    /**
     * Update issue details or status
     * PUT /v1/issues/{id}
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $issue = Issue::find($args['id']);
            if (!$issue) {
                return ResponseHelper::error($response, 'Issue not found', 404);
            }

            $data = $request->getParsedBody();
            $oldValues = $issue->toArray();
            
            // Filter allowed fields for update
            $allowed = [
                'title', 'description', 'category_id', 'sector_id', 'sub_sector_id', 
                'community_id', 'suburb_id', 'specific_location', 'details', 
                'status', 'priority', 'images', 'agent_id', 'people_affected'
            ];
            $updateData = array_intersect_key($data, array_flip($allowed));

            // Handle Image Uploads for Update
            $uploadedFiles = $request->getUploadedFiles();
            if (!empty($uploadedFiles['images'])) {
                $newImages = $this->uploadService->uploadMultipleFiles($uploadedFiles['images'], 'image', 'issues');
                
                // If the user wants to append new images or replace them
                // For now, let's append them to existing ones if 'keep_existing_images' is true, else replace
                if (!empty($data['keep_existing_images']) && (bool)$data['keep_existing_images'] === true) {
                    $existingImages = is_array($issue->images) ? $issue->images : [];
                    $updateData['images'] = array_merge($existingImages, $newImages);
                } else {
                    // If replacing, we might want to delete old files from disk
                    if (is_array($issue->images)) {
                        $this->uploadService->deleteMultipleFiles($issue->images);
                    }
                    $updateData['images'] = $newImages;
                }
            }
            
            // Handle image deletions if specifically requested
            if (!empty($data['delete_images']) && is_array($data['delete_images'])) {
                $existingImages = is_array($issue->images) ? $issue->images : [];
                $imagesToDelete = $data['delete_images'];
                
                $remainingImages = array_filter($existingImages, function($img) use ($imagesToDelete) {
                    return !in_array($img, $imagesToDelete);
                });
                
                $this->uploadService->deleteMultipleFiles($imagesToDelete);
                $updateData['images'] = array_values($remainingImages);
            }

            if (!empty($data['additional_notes']) && empty($updateData['details'])) {
                $updateData['details'] = $data['additional_notes'];
            }

            // Update constituent details if provided
            if ($issue->constituent_id) {
                $constituent = $issue->constituent;
                $constituentData = [];
                if (!empty($data['constituent_name'])) $constituentData['name'] = $data['constituent_name'];
                if (!empty($data['constituent_phone'])) $constituentData['phone_number'] = $data['constituent_phone'];
                if (!empty($data['constituent_email'])) $constituentData['email'] = $data['constituent_email'];
                if (!empty($data['constituent_gender'])) $constituentData['gender'] = $data['constituent_gender'];
                if (!empty($data['constituent_address'])) $constituentData['home_address'] = $data['constituent_address'];
                
                if (!empty($constituentData)) {
                    $constituent->update($constituentData);
                }
            }

            $issue->update($updateData);

            $this->activityLogger->logUpdate(
                $request->getAttribute('user')->id ?? null,
                'Issue',
                (int)$issue->id,
                $oldValues,
                $issue->toArray()
            );

            return ResponseHelper::success($response, 'Issue updated successfully', [
                'report' => $issue->toArray()
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to update issue', 500, $e->getMessage());
        }
    }

    /**
     * Update only the status of an issue
     * PATCH /v1/issues/{id}/status
     */
    public function updateStatus(Request $request, Response $response, array $args): Response
    {
        try {
            $issue = Issue::find($args['id']);
            if (!$issue) {
                return ResponseHelper::error($response, 'Issue not found', 404);
            }

            $data = $request->getParsedBody();
            if (empty($data['status'])) {
                return ResponseHelper::error($response, 'Status is required', 400);
            }

            $oldValues = $issue->toArray();
            $issue->status = $data['status'];
            $issue->save();

            $this->activityLogger->logUpdate(
                $request->getAttribute('user')->id ?? null,
                'Issue Status Change',
                (int)$issue->id,
                ['status' => $oldValues['status']],
                ['status' => $issue->status]
            );

            return ResponseHelper::success($response, 'Issue status updated successfully', $issue->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to update status', 500, $e->getMessage());
        }
    }

    /**
     * Mark an issue as reviewed by an officer
     * PATCH /v1/issues/{id}/review
     */
    public function review(Request $request, Response $response, array $args): Response
    {
        try {
            $issue = Issue::find($args['id']);
            if (!$issue) {
                return ResponseHelper::error($response, 'Issue not found', 404);
            }

            $authUser = $request->getAttribute('user');
            $oldValues = $issue->toArray();
            
            $issue->officer_id = $authUser->id;
            // Optionally update status to 'under_review' or similar if it's currently 'submitted'
            if ($issue->status === Issue::STATUS_SUBMITTED) {
                $issue->status = 'under_review'; // Assuming this status exists or we use a general one
            }
            $issue->save();

            $this->activityLogger->logUpdate(
                $authUser->id,
                'Issue Review',
                (int)$issue->id,
                $oldValues,
                $issue->toArray()
            );

            return ResponseHelper::success($response, 'Issue marked as reviewed', $issue->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to review issue', 500, $e->getMessage());
        }
    }

    /**
     * Delete an issue report
     * DELETE /v1/issues/{id}
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $issue = Issue::find($args['id']);
            if (!$issue) {
                return ResponseHelper::error($response, 'Issue not found', 404);
            }

            $oldValues = $issue->toArray();
            $issueId = (int)$issue->id;
            $issue->delete();

            $this->activityLogger->logDelete(
                $request->getAttribute('user')->id ?? null,
                'Issue',
                $issueId,
                $oldValues
            );

            return ResponseHelper::success($response, 'Issue deleted successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to delete issue', 500, $e->getMessage());
        }
    }
}
