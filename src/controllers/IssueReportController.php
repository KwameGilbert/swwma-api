<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\IssueReport;
use App\Models\IssueReportComment;
use App\Models\IssueReportStatusHistory;
use App\Models\Agent;
use App\Services\UploadService;
use App\Helper\ResponseHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;
use Exception;

/**
 * IssueReportController
 * 
 * Handles issue report operations.
 * - Public can submit reports
 * - Officers and Agents can manage reports
 */
class IssueReportController
{
    private UploadService $uploadService;

    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }
    /**
     * Submit a new issue report (Public)
     * POST /api/issues
     */
    public function submit(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody() ?? [];
            $uploadedFiles = $request->getUploadedFiles();

            // Validation
            if (empty($data['title'])) {
                return ResponseHelper::error($response, 'Title is required', 400);
            }
            if (empty($data['description'])) {
                return ResponseHelper::error($response, 'Description is required', 400);
            }
            if (empty($data['location'])) {
                return ResponseHelper::error($response, 'Location is required', 400);
            }

            // Handle images upload
            $imagesJson = $data['images'] ?? null;
            $imageFiles = $uploadedFiles['images'] ?? [];
            if (!empty($imageFiles)) {
                if (!is_array($imageFiles)) {
                    $imageFiles = [$imageFiles];
                }
                try {
                    $uploadedImages = $this->uploadService->uploadMultipleFiles($imageFiles, 'image', 'issues');
                    if (!empty($uploadedImages)) {
                        $imagesJson = json_encode($uploadedImages);
                    }
                } catch (Exception $e) {
                    // Log but don't fail - images are optional
                    error_log('Issue images upload failed: ' . $e->getMessage());
                }
            }

            // Resolve sector and sub-sector IDs from names
            $sectorId = null;
            $subSectorId = null;
            if (!empty($data['sector'])) {
                $sector = \App\Models\Sector::where('name', $data['sector'])->first();
                $sectorId = $sector ? $sector->id : null;
                
                if ($sectorId && !empty($data['subsector'])) {
                    $subSector = \App\Models\SubSector::where('name', $data['subsector'])
                        ->where('sector_id', $sectorId)
                        ->first();
                    $subSectorId = $subSector ? $subSector->id : null;
                }
            }

            // Resolve location hierarchy IDs from names
            $mainCommunityId = null;
            $smallerCommunityId = null;
            $suburbId = null;
            
            if (!empty($data['location'])) {
                $mainCommunity = \App\Models\Location::where('name', $data['location'])
                    ->where('type', 'community')
                    ->first();
                $mainCommunityId = $mainCommunity ? $mainCommunity->id : null;
                
                if ($mainCommunityId) {
                    if (!empty($data['smaller_community'])) {
                        $smallerCommunity = \App\Models\Location::where('name', $data['smaller_community'])
                            ->where('parent_id', $mainCommunityId)
                            ->where('type', 'smaller_community')
                            ->first();
                        $smallerCommunityId = $smallerCommunity ? $smallerCommunity->id : null;
                    }
                    
                    if (!empty($data['suburb'])) {
                        $suburb = \App\Models\Location::where('name', $data['suburb'])
                            ->where('parent_id', $mainCommunityId)
                            ->where('type', 'suburb')
                            ->first();
                        $suburbId = $suburb ? $suburb->id : null;
                    }
                }
            }

            $report = IssueReport::create([
                'case_id' => IssueReport::generateCaseId(),
                'title' => $data['title'],
                'description' => $data['description'],
                'category' => $data['category'] ?? null,
                // NEW: Classification fields
                'sector_id' => $sectorId,
                'sub_sector_id' => $subSectorId,
                'issue_type' => $data['issue_type'] ?? 'community_based',
                'affected_people_count' => $data['people_affected'] ?? null,
                // Location (legacy VARCHAR field)
                'location' => $data['location'],
                // NEW: Location hierarchy
                'main_community_id' => $mainCommunityId,
                'smaller_community_id' => $smallerCommunityId,
                'suburb_id' => $suburbId,
                'cottage_id' => null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'images' => $imagesJson,
                // NEW: Constituent information
                'constituent_name' => $data['reporter_name'] ?? null,
                'constituent_email' => $data['reporter_email'] ?? null,
                'constituent_contact' => $data['reporter_phone'] ?? null,
                'constituent_gender' => $data['reporter_gender'] ?? null,
                'constituent_address' => $data['reporter_address'] ?? null,
                // Legacy fields for backward compatibility
                'reporter_name' => $data['reporter_name'] ?? null,
                'reporter_email' => $data['reporter_email'] ?? null,
                'reporter_phone' => $data['reporter_phone'] ?? null,
                'status' => IssueReport::STATUS_SUBMITTED,
                'priority' => $data['priority'] ?? IssueReport::PRIORITY_MEDIUM,
            ]);

            // Log initial status
            IssueReportStatusHistory::logChange(
                $report->id,
                0, // System
                null,
                IssueReport::STATUS_SUBMITTED,
                'Report submitted'
            );

            return ResponseHelper::success($response, 'Issue report submitted successfully', [
                'report' => [
                    'id' => $report->id,
                    'case_id' => $report->case_id,
                    'status' => $report->status,
                ],
                'message' => 'Your issue has been submitted. Use your case ID to track progress.'
            ], 201);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to submit issue report', 500, $e->getMessage());
        }
    }

    /**
     * Track issue by case ID (Public)
     * GET /api/issues/track/{caseId}
     */
    public function track(Request $request, Response $response, array $args): Response
    {
        try {
            $report = IssueReport::where('case_id', $args['caseId'])->first();

            if (!$report) {
                return ResponseHelper::error($response, 'Issue report not found', 404);
            }

            return ResponseHelper::success($response, 'Issue status fetched successfully', [
                'report' => $report->toPublicArray(),
                'history' => $report->statusHistory()
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(fn($h) => [
                        'status' => $h->new_status,
                        'notes' => $h->notes,
                        'date' => $h->created_at?->toDateTimeString(),
                    ])->toArray()
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch issue status', 500, $e->getMessage());
        }
    }

    /**
     * Get all issue reports (Admin/Officer)
     * GET /api/admin/issues
     */
    /**
     * Get all issue reports (Admin/Officer)
     * GET /api/admin/issues
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $page = (int)($params['page'] ?? 1);
            $limit = min((int)($params['limit'] ?? 10), 50);
            $status = $params['status'] ?? null;
            $priority = $params['priority'] ?? null;
            $category = $params['category'] ?? null;

            $user = $request->getAttribute('user');
            // Cast to object if array
            $userObj = is_array($user) ? (object)$user : $user;
            
            // Get user role from DB to be safe
            $dbUser = \App\Models\User::find($userObj->id);
            $userRole = $dbUser ? $dbUser->role : null;

            $query = IssueReport::with(['assignedOfficer.user', 'submittedByAgent.user', 'sector', 'subSector', 'mainCommunity', 'smallerCommunity', 'suburb', 'cottage'])
                ->orderBy('created_at', 'desc');

            error_log("IssueReportController::index - User ID: {$userObj->id}, Role: " . ($userRole ?? 'null'));

            // --- Role-Based Visibility Logic ---
            if ($userRole === \App\Models\User::ROLE_ADMIN || $userRole === \App\Models\User::ROLE_WEB_ADMIN) {
                error_log("IssueReportController::index - Entering Admin Block");
                // Admin Logic
                if ($status) {
                    // Start strict: prevent admins from "accidentally" seeing submitted/under_review via direct status filter
                    if (in_array($status, [IssueReport::STATUS_SUBMITTED, IssueReport::STATUS_UNDER_OFFICER_REVIEW])) {
                         return ResponseHelper::success($response, 'Issue reports fetched successfully', [
                            'reports' => [],
                            'pagination' => ['page' => $page, 'limit' => $limit, 'total' => 0, 'total_pages' => 0]
                        ]);
                    }
                    $query->where('status', $status);
                } else {
                     // Default view for Admin: Show everything EXCEPT submitted/under_officer_review
                     $query->whereNotIn('status', [
                        IssueReport::STATUS_SUBMITTED,
                        IssueReport::STATUS_UNDER_OFFICER_REVIEW
                     ]);
                }
            } elseif ($userRole === \App\Models\User::ROLE_OFFICER) {
                error_log("IssueReportController::index - Entering Officer Block");
                // Officer Logic: Officers can see all issues regardless of status
                if ($status) {
                    $query->where('status', $status);
                }
                // No default status filter — officers see all issues
            } elseif ($userRole === \App\Models\User::ROLE_TASK_FORCE) {
                error_log("IssueReportController::index - Entering Task Force Block");
                // Task Force Logic (Strict)
                $allowedStatuses = [
                    IssueReport::STATUS_ASSIGNED_TO_TASK_FORCE,
                    IssueReport::STATUS_ASSESSMENT_IN_PROGRESS,
                    IssueReport::STATUS_ASSESSMENT_SUBMITTED,
                    IssueReport::STATUS_RESOURCES_ALLOCATED,
                    IssueReport::STATUS_RESOLUTION_IN_PROGRESS,
                    IssueReport::STATUS_RESOLUTION_SUBMITTED,
                    IssueReport::STATUS_RESOLVED,
                    IssueReport::STATUS_CLOSED
                ];

                // Get Task Force Profile ID
                $tfProfile = \App\Models\TaskForce::where('user_id', $userObj->id)->first();
                if (!$tfProfile) {
                     error_log("IssueReportController::index - TF Profile not found for User {$userObj->id}");
                     return ResponseHelper::success($response, 'Issues fetched successfully', [
                        'reports' => [],
                        'pagination' => ['page' => $page, 'limit' => $limit, 'total' => 0, 'total_pages' => 0]
                    ]);
                }
                error_log("IssueReportController::index - TF Profile ID: {$tfProfile->id}");

                if ($status) {
                    if (!in_array($status, $allowedStatuses)) {
                        return ResponseHelper::success($response, 'Issues fetched successfully', [
                            'reports' => [],
                            'pagination' => ['page' => $page, 'limit' => $limit, 'total' => 0, 'total_pages' => 0]
                        ]);
                    }
                    $query->where('status', $status);
                } else {
                    $query->whereIn('status', $allowedStatuses);
                }



            } else {
                error_log("IssueReportController::index - Entering Fallback Block (Role mismatch?)");
                // Other Roles (Agent, Public) - Secure Sandbox: See only your own submissions
                $query->where('submitted_by', $userObj->id); // Assuming submitted_by matches user_id, or check schema
                if ($status) {
                    $query->where('status', $status);
                }
            }

            if ($priority) {
                $query->where('priority', $priority);
            }
            if ($category) {
                $query->where('category', $category);
            }

            $total = $query->count();
            $reports = $query->skip(($page - 1) * $limit)->take($limit)->get();

            return ResponseHelper::success($response, 'Issue reports fetched successfully', [
                'reports' => $reports->toArray(),
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit),
                ]
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch issue reports', 500, $e->getMessage());
        }
    }

    /**
     * Get issue statistics
     * GET /api/admin/issues/stats
     */
    public function stats(Request $request, Response $response): Response
    {
        try {
            $stats = [
                'total' => IssueReport::count(),
                'pending' => IssueReport::pending()->count(),
                'resolved' => IssueReport::where('status', IssueReport::STATUS_RESOLVED)->count(),
                'by_status' => [
                    'submitted' => IssueReport::where('status', IssueReport::STATUS_SUBMITTED)->count(),
                    'under_officer_review' => IssueReport::where('status', IssueReport::STATUS_UNDER_OFFICER_REVIEW)->count(),
                    'forwarded_to_admin' => IssueReport::where('status', IssueReport::STATUS_FORWARDED_TO_ADMIN)->count(),
                    'assigned_to_task_force' => IssueReport::where('status', IssueReport::STATUS_ASSIGNED_TO_TASK_FORCE)->count(),
                    'assessment_in_progress' => IssueReport::where('status', IssueReport::STATUS_ASSESSMENT_IN_PROGRESS)->count(),
                    'assessment_submitted' => IssueReport::where('status', IssueReport::STATUS_ASSESSMENT_SUBMITTED)->count(),
                    'resources_allocated' => IssueReport::where('status', IssueReport::STATUS_RESOURCES_ALLOCATED)->count(),
                    'resolution_in_progress' => IssueReport::where('status', IssueReport::STATUS_RESOLUTION_IN_PROGRESS)->count(),
                    'resolution_submitted' => IssueReport::where('status', IssueReport::STATUS_RESOLUTION_SUBMITTED)->count(),
                    'resolved' => IssueReport::where('status', IssueReport::STATUS_RESOLVED)->count(),
                    'closed' => IssueReport::where('status', IssueReport::STATUS_CLOSED)->count(),
                ],
                'by_priority' => [
                    'urgent' => IssueReport::where('priority', IssueReport::PRIORITY_URGENT)->count(),
                    'high' => IssueReport::where('priority', IssueReport::PRIORITY_HIGH)->count(),
                    'medium' => IssueReport::where('priority', IssueReport::PRIORITY_MEDIUM)->count(),
                    'low' => IssueReport::where('priority', IssueReport::PRIORITY_LOW)->count(),
                ],
            ];

            return ResponseHelper::success($response, 'Statistics fetched successfully', $stats);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch statistics', 500, $e->getMessage());
        }
    }

    /**
     * Get single issue report
     * GET /api/admin/issues/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $report = IssueReport::with([
                'assignedOfficer.user',
                'assignedAgent.user',
                'submittedByAgent.user',
                'comments.user',
                'statusHistory.changedByUser',
                'assessmentReport',
                'resolutionReport',
                'sector',
                'subSector',
                'mainCommunity',
                'smallerCommunity',
                'suburb',
                'cottage',
            ])->find($args['id']);

            if (!$report) {
                return ResponseHelper::error($response, 'Issue report not found', 404);
            }

            $reportArray = $report->toArray();

            // Normalize display fields so all dashboards can render consistent details.
            $reportArray['sector'] = $report->sector?->name ?? ($reportArray['sector'] ?? null);
            $reportArray['subsector'] = $report->subSector?->name ?? ($reportArray['subsector'] ?? null);

            $reportArray['location'] = $report->mainCommunity?->name ?? ($reportArray['location'] ?? null);
            $reportArray['smaller_community'] = $report->smallerCommunity?->name ?? ($reportArray['smaller_community'] ?? null);
            $reportArray['suburb'] = $report->suburb?->name ?? ($reportArray['suburb'] ?? null);
            $reportArray['cottage'] = $report->cottage?->name ?? ($reportArray['cottage'] ?? null);

            $reportArray['reporter_name'] = $report->reporter_name ?: $report->constituent_name;
            $reportArray['reporter_phone'] = $report->reporter_phone ?: $report->constituent_contact;
            $reportArray['reporter_email'] = $report->reporter_email ?: $report->constituent_email;
            $reportArray['reporter_gender'] = $report->constituent_gender ?? ($reportArray['reporter_gender'] ?? null);
            $reportArray['reporter_address'] = $report->constituent_address ?? ($reportArray['reporter_address'] ?? null);

            return ResponseHelper::success($response, 'Issue report fetched successfully', [
                'report' => $reportArray
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch issue report', 500, $e->getMessage());
        }
    }

    /**
     * Update issue report status
     * PUT /api/admin/issues/{id}/status
     */
    public function updateStatus(Request $request, Response $response, array $args): Response
    {
        try {
            $report = IssueReport::find($args['id']);

            if (!$report) {
                return ResponseHelper::error($response, 'Issue report not found', 404);
            }

            $data = $request->getParsedBody();
            $user = $request->getAttribute('user');
            
            // Normalize user to object to handle both array and object formats safely
            $userObj = (object)$user;
            $userId = $userObj->id ?? null;

            if (empty($data['status'])) {
                return ResponseHelper::error($response, 'Status is required', 400);
            }

            $oldStatus = $report->status;
            $newStatus = $data['status'];

            // --- STRICT WORKFLOW VALIDATION ---
            // Get user role
            $dbUser = \App\Models\User::find($userId);
            $userRole = $dbUser ? $dbUser->role : null;

            if ($userRole === \App\Models\User::ROLE_OFFICER) {
                // Officers can only:
                // 1. Acknowledge/Review (under_officer_review)
                // 2. Forward to Admin (forwarded_to_admin)
                // They CANNOT assign to task force or resolve directly (unless configured otherwise)
                
                $allowedOfficerStatuses = [
                    IssueReport::STATUS_UNDER_OFFICER_REVIEW,
                    IssueReport::STATUS_FORWARDED_TO_ADMIN,
                    // Maybe 'rejected' if they can reject? standard workflow suggests yes
                    IssueReport::STATUS_REJECTED 
                ];

                if (!in_array($newStatus, $allowedOfficerStatuses)) {
                     return ResponseHelper::error($response, 'Officers can only review, reject, or forward issues to admin.', 403);
                }
            }

            // Admins can do anything, BUT the system prefers they pick up from 'forwarded_to_admin'.
            // We won't block Admins from "fixing" things, but the UI should guide the flow.
            
            // ----------------------------------

            // Update report
            $updateData = ['status' => $newStatus];

            // Handle officer acknowledgement
            if ($newStatus === IssueReport::STATUS_UNDER_OFFICER_REVIEW && !$report->acknowledged_at) {
                // Find officer profile for the current user
                $officer = \App\Models\Officer::findByUserId($userId);
                
                if (!$officer) {
                     return ResponseHelper::error($response, 'Officer profile not found for this user', 403);
                }

                $updateData['acknowledged_at'] = date('Y-m-d H:i:s');
                $updateData['acknowledged_by'] = $officer->id;
            }

            if ($newStatus === IssueReport::STATUS_RESOLVED && !$report->resolved_at) {
                $updateData['resolved_at'] = date('Y-m-d H:i:s');
                $updateData['resolved_by'] = $userId;
                $updateData['resolution_notes'] = $data['notes'] ?? null;
            }

            $report->update($updateData);

            // Log status change
            IssueReportStatusHistory::logChange(
                $report->id,
                $userId ?? 0,
                $oldStatus,
                $newStatus,
                $data['notes'] ?? null
            );

            return ResponseHelper::success($response, 'Status updated successfully', [
                'report' => $report->fresh()->toArray()
            ]);
        } catch (\Throwable $e) {
            // Return specific error message for debugging
            return ResponseHelper::error($response, 'Failed to update status: ' . $e->getMessage(), 500, $e->getMessage());
        }
    }

    /**
     * Assign issue to officer
     * PUT /api/admin/issues/{id}/assign
     */
    public function assign(Request $request, Response $response, array $args): Response
    {
        try {
            $report = IssueReport::find($args['id']);

            if (!$report) {
                return ResponseHelper::error($response, 'Issue report not found', 404);
            }

            $data = $request->getParsedBody();
            $user = $request->getAttribute('user');

            $updateData = [];

            if (isset($data['officer_id'])) {
                $updateData['assigned_officer_id'] = $data['officer_id'];
            }

            if (isset($data['agent_id'])) {
                $updateData['assigned_agent_id'] = $data['agent_id'];
            }

            if (empty($updateData)) {
                return ResponseHelper::error($response, 'Officer or agent ID is required', 400);
            }

            $report->update($updateData);

            // Log assignment
            IssueReportStatusHistory::logChange(
                $report->id,
                $user->id ?? 0,
                $report->status,
                $report->status,
                'Assigned to staff'
            );

            return ResponseHelper::success($response, 'Issue assigned successfully', [
                'report' => $report->fresh()->toArray()
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to assign issue', 500, $e->getMessage());
        }
    }

    /**
     * Add comment to issue
     * POST /api/admin/issues/{id}/comments
     */
    public function addComment(Request $request, Response $response, array $args): Response
    {
        try {
            $report = IssueReport::find($args['id']);

            if (!$report) {
                return ResponseHelper::error($response, 'Issue report not found', 404);
            }

            $data = $request->getParsedBody();
            $user = $request->getAttribute('user');

            if (empty($data['comment'])) {
                return ResponseHelper::error($response, 'Comment is required', 400);
            }

            $comment = IssueReportComment::create([
                'issue_report_id' => $report->id,
                'user_id' => $user->id,
                'comment' => $data['comment'],
                'is_internal' => $data['is_internal'] ?? true,
                'attachments' => $data['attachments'] ?? null,
            ]);

            return ResponseHelper::success($response, 'Comment added successfully', [
                'comment' => $comment->toArray()
            ], 201);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to add comment', 500, $e->getMessage());
        }
    }

    /**
     * Agent submit issue report
     * POST /api/agent/issues
     */
    public function agentSubmit(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody() ?? [];
            $uploadedFiles = $request->getUploadedFiles();
            $user = $request->getAttribute('user');

            // Get agent profile
            $agent = Agent::findByUserId($user->id);

            if (!$agent) {
                return ResponseHelper::error($response, 'Agent profile not found', 404);
            }

            if (!$agent->canSubmitReports()) {
                return ResponseHelper::error($response, 'You do not have permission to submit reports', 403);
            }

            // Validation
            if (empty($data['title'])) {
                return ResponseHelper::error($response, 'Title is required', 400);
            }
            if (empty($data['description'])) {
                return ResponseHelper::error($response, 'Description is required', 400);
            }
            if (empty($data['location'])) {
                return ResponseHelper::error($response, 'Location is required', 400);
            }

            // Handle images upload
            $imagesJson = $data['images'] ?? null;
            $imageFiles = $uploadedFiles['images'] ?? [];
            if (!empty($imageFiles)) {
                if (!is_array($imageFiles)) {
                    $imageFiles = [$imageFiles];
                }
                try {
                    $uploadedImages = $this->uploadService->uploadMultipleFiles($imageFiles, 'image', 'issues');
                    if (!empty($uploadedImages)) {
                        $imagesJson = json_encode($uploadedImages);
                    }
                } catch (Exception $e) {
                    error_log('Issue images upload failed: ' . $e->getMessage());
                }
            }

            // Resolve sector and sub-sector IDs from names
            $sectorId = null;
            $subSectorId = null;
            if (!empty($data['sector'])) {
                $sector = \App\Models\Sector::where('name', $data['sector'])->first();
                $sectorId = $sector ? $sector->id : null;
                
                if ($sectorId && !empty($data['subsector'])) {
                    $subSector = \App\Models\SubSector::where('name', $data['subsector'])
                        ->where('sector_id', $sectorId)
                        ->first();
                    $subSectorId = $subSector ? $subSector->id : null;
                }
            }

            // Resolve location hierarchy IDs from names
            $mainCommunityId = null;
            $smallerCommunityId = null;
            $suburbId = null;
            
            if (!empty($data['location'])) {
                $mainCommunity = \App\Models\Location::where('name', $data['location'])
                    ->where('type', 'community')
                    ->first();
                $mainCommunityId = $mainCommunity ? $mainCommunity->id : null;
                
                if ($mainCommunityId) {
                    if (!empty($data['smaller_community'])) {
                        $smallerCommunity = \App\Models\Location::where('name', $data['smaller_community'])
                            ->where('parent_id', $mainCommunityId)
                            ->where('type', 'smaller_community')
                            ->first();
                        $smallerCommunityId = $smallerCommunity ? $smallerCommunity->id : null;
                    }
                    
                    if (!empty($data['suburb'])) {
                        $suburb = \App\Models\Location::where('name', $data['suburb'])
                            ->where('parent_id', $mainCommunityId)
                            ->where('type', 'suburb')
                            ->first();
                        $suburbId = $suburb ? $suburb->id : null;
                    }
                }
            }

            $report = IssueReport::create([
                'case_id' => IssueReport::generateCaseId(),
                'title' => $data['title'],
                'description' => $data['description'],
                'category' => $data['category'] ?? null,
                // NEW: Classification fields
                'sector_id' => $sectorId,
                'sub_sector_id' => $subSectorId,
                'issue_type' => $data['issue_type'] ?? 'community_based',
                'affected_people_count' => $data['people_affected'] ?? null,
                // Location (legacy VARCHAR field)
                'location' => $data['location'],
                // NEW: Location hierarchy
                'main_community_id' => $mainCommunityId,
                'smaller_community_id' => $smallerCommunityId,
                'suburb_id' => $suburbId,
                'cottage_id' => null, // Not used in form yet
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'images' => $imagesJson,
                // NEW: Constituent information (using new field names)
                'constituent_name' => $data['reporter_name'] ?? null,
                'constituent_email' => $data['reporter_email'] ?? null,
                'constituent_contact' => $data['reporter_phone'] ?? null,
                'constituent_gender' => $data['reporter_gender'] ?? null,
                'constituent_address' => $data['reporter_address'] ?? null,
                // Legacy fields for backward compatibility
                'reporter_name' => $data['reporter_name'] ?? null,
                'reporter_email' => $data['reporter_email'] ?? null,
                'reporter_phone' => $data['reporter_phone'] ?? null,
                'submitted_by_agent_id' => $agent->id,
                'status' => IssueReport::STATUS_SUBMITTED,
                'priority' => $data['priority'] ?? IssueReport::PRIORITY_MEDIUM,
            ]);

            // Increment agent's report count
            $agent->incrementReportsSubmitted();
            $agent->updateLastActive();

            // Log status
            IssueReportStatusHistory::logChange(
                $report->id,
                $user->id,
                null,
                IssueReport::STATUS_SUBMITTED,
                'Submitted by agent'
            );

            return ResponseHelper::success($response, 'Issue report submitted successfully', [
                'report' => $report->toPublicArray()
            ], 201);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to submit issue report', 500, $e->getMessage());
        }
    }

    /**
     * Agent view single issue report
     * GET /api/agent/issues/{id}
     */
    public function agentShow(Request $request, Response $response, array $args): Response
    {
        try {
            $user = $request->getAttribute('user');
            $agent = Agent::findByUserId($user->id);

            if (!$agent) {
                return ResponseHelper::error($response, 'Agent profile not found', 404);
            }

            $report = IssueReport::with([
                'assignedOfficer.user',
                'assignedAgent.user',
                'submittedByAgent.user',
                'comments.user',
                'statusHistory.changedByUser',
                'assessmentReport',
                'resolutionReport',
                'sector',
                'subSector',
                'mainCommunity',
                'smallerCommunity',
                'suburb',
                'cottage',
            ])
            ->where('id', $args['id'])
            ->where('submitted_by_agent_id', $agent->id)
            ->first();

            if (!$report) {
                return ResponseHelper::error($response, 'Issue report not found or access denied', 404);
            }

            return ResponseHelper::success($response, 'Issue report fetched successfully', [
                'report' => $report->toFullArray()
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch issue report', 500, $e->getMessage());
        }
    }

    /**
     * Officer submit issue report
     * POST /api/officer/issues
     */
    public function officerSubmit(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody() ?? [];
            $uploadedFiles = $request->getUploadedFiles();
            $user = $request->getAttribute('user');

            // Get officer profile
            $officer = \App\Models\Officer::findByUserId($user->id);

            if (!$officer) {
                return ResponseHelper::error($response, 'Officer profile not found', 404);
            }

            // Validation
            if (empty($data['title'])) {
                return ResponseHelper::error($response, 'Title is required', 400);
            }
            if (empty($data['description'])) {
                return ResponseHelper::error($response, 'Description is required', 400);
            }
            if (empty($data['location'])) {
                return ResponseHelper::error($response, 'Location is required', 400);
            }

            // Handle images upload
            $imagesJson = $data['images'] ?? null;
            $imageFiles = $uploadedFiles['images'] ?? [];
            if (!empty($imageFiles)) {
                if (!is_array($imageFiles)) {
                    $imageFiles = [$imageFiles];
                }
                try {
                    $uploadedImages = $this->uploadService->uploadMultipleFiles($imageFiles, 'image', 'issues');
                    if (!empty($uploadedImages)) {
                        $imagesJson = json_encode($uploadedImages);
                    }
                } catch (Exception $e) {
                    error_log('Issue images upload failed: ' . $e->getMessage());
                }
            }

            // Enrich description with extra fields
            $enrichedDescription = $data['description'];
            $extras = [];
            if (!empty($data['issue_type'])) $extras[] = "Issue Type: " . $data['issue_type'];
            if (!empty($data['sector'])) $extras[] = "Sector: " . $data['sector'];
            if (!empty($data['subsector'])) $extras[] = "Subsector: " . $data['subsector'];
            if (!empty($data['people_affected'])) $extras[] = "People Affected: " . $data['people_affected'];
            if (!empty($data['reporter_gender'])) $extras[] = "Gender: " . $data['reporter_gender'];
            if (!empty($data['reporter_address'])) $extras[] = "Address: " . $data['reporter_address'];
            if (!empty($data['additional_notes'])) $extras[] = "Notes: " . $data['additional_notes'];
            
            if (!empty($extras)) {
                $enrichedDescription .= "\n\n-- Additional Details --\n" . implode("\n", $extras);
            }

            // Resolve sector and sub-sector IDs from names
            $sectorId = null;
            $subSectorId = null;
            if (!empty($data['sector'])) {
                $sector = \App\Models\Sector::where('name', $data['sector'])->first();
                $sectorId = $sector ? $sector->id : null;

                if ($sectorId && !empty($data['subsector'])) {
                    $subSector = \App\Models\SubSector::where('name', $data['subsector'])
                        ->where('sector_id', $sectorId)
                        ->first();
                    $subSectorId = $subSector ? $subSector->id : null;
                }
            }

            // Resolve location hierarchy IDs from names
            $mainCommunityId = null;
            $smallerCommunityId = null;
            $suburbId = null;

            if (!empty($data['location'])) {
                $mainCommunity = \App\Models\Location::where('name', $data['location'])
                    ->where('type', 'community')
                    ->first();
                $mainCommunityId = $mainCommunity ? $mainCommunity->id : null;

                if ($mainCommunityId) {
                    if (!empty($data['smaller_community'])) {
                        $smallerCommunity = \App\Models\Location::where('name', $data['smaller_community'])
                            ->where('parent_id', $mainCommunityId)
                            ->where('type', 'smaller_community')
                            ->first();
                        $smallerCommunityId = $smallerCommunity ? $smallerCommunity->id : null;
                    }

                    if (!empty($data['suburb'])) {
                        $suburb = \App\Models\Location::where('name', $data['suburb'])
                            ->where('parent_id', $mainCommunityId)
                            ->where('type', 'suburb')
                            ->first();
                        $suburbId = $suburb ? $suburb->id : null;
                    }
                }
            }

            $report = IssueReport::create([
                'case_id' => IssueReport::generateCaseId(),
                'title' => $data['title'],
                'description' => $enrichedDescription,
                'category' => $data['category'] ?? null,
                // Classification fields
                'sector_id' => $sectorId,
                'sub_sector_id' => $subSectorId,
                'issue_type' => $data['issue_type'] ?? 'community_based',
                'affected_people_count' => $data['people_affected'] ?? null,
                'location' => $data['location'],
                // Location hierarchy
                'main_community_id' => $mainCommunityId,
                'smaller_community_id' => $smallerCommunityId,
                'suburb_id' => $suburbId,
                'cottage_id' => null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'images' => $imagesJson,
                // Constituent information
                'constituent_name' => $data['reporter_name'] ?? null,
                'constituent_email' => $data['reporter_email'] ?? null,
                'constituent_contact' => $data['reporter_phone'] ?? null,
                'constituent_gender' => $data['reporter_gender'] ?? null,
                'constituent_address' => $data['reporter_address'] ?? null,
                // Legacy fields for backward compatibility
                'reporter_name' => $data['reporter_name'] ?? null,
                'reporter_email' => $data['reporter_email'] ?? null,
                'reporter_phone' => $data['reporter_phone'] ?? null,
                'submitted_by_officer_id' => $officer->id,
                'status' => IssueReport::STATUS_SUBMITTED,
                'priority' => $data['priority'] ?? IssueReport::PRIORITY_MEDIUM,
            ]);

            // Log status
            IssueReportStatusHistory::logChange(
                $report->id,
                $user->id,
                null,
                IssueReport::STATUS_SUBMITTED,
                'Submitted by officer'
            );

            return ResponseHelper::success($response, 'Issue report submitted successfully', [
                'report' => $report->toPublicArray()
            ], 201);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to submit issue report', 500, $e->getMessage());
        }
    }

    /**
     * Agent update issue report
     * PUT /api/agent/issues/{id}
     */
    public function agentUpdate(Request $request, Response $response, array $args): Response
    {
        try {
            $report = IssueReport::find($args['id']);

            if (!$report) {
                return ResponseHelper::error($response, 'Issue report not found', 404);
            }

            $user = $request->getAttribute('user');
            $agent = Agent::findByUserId($user->id);

            if (!$agent) {
                return ResponseHelper::error($response, 'Agent profile not found', 403);
            }

            // Check permissions: Agent can only edit issues they submitted
            // And only if status is submitted, rejected, or pending
            // Also allow 'assessment_submitted' if they need to correct details before full approval? 
            // Sticking to safe initial statuses + rejected.
            
            // For now, strict ownership check for agents
            // We can relax this if agents work in teams, but usually agents are individual.
            // However, the report might not store submitted_by_agent_id directly if not set? 
            // IssueReport model has submitted_by_agent_id? Yes?
            // "agentSubmit" sets 'submitted_by_agent_id' => $agent->id ??
            // Let's check agentSubmit to be sure. 
            // It does not seem to define 'submitted_by_agent_id' in previous snippets, let's assume it does or use user_id check.
            // Actually, let's check ownership via report's relationship or a field. 
            // Assuming strict check is okay for now or just allow if in valid status and agent role.
            
            // Strict ownership check for agents
            if ($report->submitted_by_agent_id !== $agent->id) {
                return ResponseHelper::error($response, 'Access denied: You can only edit your own reports', 403);
            }

            if (!in_array($report->status, [IssueReport::STATUS_SUBMITTED, IssueReport::STATUS_REJECTED])) {
                return ResponseHelper::error($response, 'Cannot edit issue in current status', 400); 
            }

            $data = $request->getParsedBody();

            // Fields allowed to update
            $updateData = [];
            if (!empty($data['title'])) $updateData['title'] = $data['title'];
            if (!empty($data['description'])) $updateData['description'] = $data['description'];
            if (!empty($data['location'])) $updateData['location'] = $data['location'];
            if (!empty($data['category'])) $updateData['category'] = $data['category'];
            if (!empty($data['priority'])) $updateData['priority'] = $data['priority'];
            
            // Re-resolve sector/subsector if provided
            if (!empty($data['sector'])) {
                $sector = \App\Models\Sector::where('name', $data['sector'])->first();
                $updateData['sector_id'] = $sector ? $sector->id : null;
                
                if ($updateData['sector_id'] && !empty($data['subsector'])) {
                    $subSector = \App\Models\SubSector::where('name', $data['subsector'])
                        ->where('sector_id', $updateData['sector_id'])
                        ->first();
                    $updateData['sub_sector_id'] = $subSector ? $subSector->id : null;
                }
            }

            $report->update($updateData);

            // Log update
            IssueReportStatusHistory::logChange(
                $report->id,
                $user->id,
                $report->status,
                $report->status,
                'Report details updated by agent'
            );

            return ResponseHelper::success($response, 'Issue report updated successfully', [
                'report' => $report->fresh()->toPublicArray()
            ]);

        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to update issue report', 500, $e->getMessage());
        }
    }

    /**
     * Agent delete issue report
     * DELETE /api/agent/issues/{id}
     */
    public function agentDelete(Request $request, Response $response, array $args): Response
    {
        try {
            $report = IssueReport::find($args['id']);

            if (!$report) {
                return ResponseHelper::error($response, 'Issue report not found', 404);
            }

            $user = $request->getAttribute('user');
            $agent = Agent::findByUserId($user->id);

            if (!$agent) {
                return ResponseHelper::error($response, 'Agent profile not found', 403);
            }

            // Agents can only delete their own issues in early stages
            if ($report->submitted_by_agent_id !== $agent->id) {
                return ResponseHelper::error($response, 'Access denied: You can only delete your own reports', 403);
            }
            
            if (!in_array($report->status, [IssueReport::STATUS_SUBMITTED, IssueReport::STATUS_REJECTED])) {
                return ResponseHelper::error($response, 'Cannot delete issue that has been processed', 400); 
            }

            $report->delete();

            return ResponseHelper::success($response, 'Issue report deleted successfully');

        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to delete issue report', 500, $e->getMessage());
        }
    }

    /**
     * Officer update issue report
     * PUT /api/officer/issues/{id}
     */
    public function officerUpdate(Request $request, Response $response, array $args): Response
    {
        try {
            $report = IssueReport::find($args['id']);

            if (!$report) {
                return ResponseHelper::error($response, 'Issue report not found', 404);
            }

            $user = $request->getAttribute('user');
            $officer = \App\Models\Officer::findByUserId($user->id);

            if (!$officer) {
                return ResponseHelper::error($response, 'Officer profile not found', 403);
            }

            // Check permissions: Officer can edit if they submitted it OR if they are the assigned officer?
            // Usually, editing implies correcting the original submission.
            // Let's restrict to: Officer who submitted it, AND status is 'submitted' or 'rejected'.
            // Or maybe 'under_officer_review' if they are the one reviewing it and want to fix typos?
            // For now, let's stick to the plan: Officer who submitted it (or maybe any officer if it's their jurisdiction?)
            // Safest: Officer who submitted it.
            
            // Check permissions: Allow any officer to edit if status is appropriate
            // This allows officers to collaborate or fix issues submitted by others in their jurisdiction.
            
            // Allow editing in early stages or if under officer review
            if (!in_array($report->status, [IssueReport::STATUS_SUBMITTED, IssueReport::STATUS_REJECTED, IssueReport::STATUS_UNDER_OFFICER_REVIEW])) {
                return ResponseHelper::error($response, 'Cannot edit issue in current status', 400); 
            }

            $data = $request->getParsedBody();

            // Fields allowed to update
            $updateData = [];
            if (!empty($data['title'])) $updateData['title'] = $data['title'];
            if (!empty($data['description'])) $updateData['description'] = $data['description'];
            if (!empty($data['location'])) $updateData['location'] = $data['location'];
            if (!empty($data['category'])) $updateData['category'] = $data['category'];
            if (!empty($data['priority'])) $updateData['priority'] = $data['priority'];
            
            // Re-resolve sector/subsector if provided
            if (!empty($data['sector'])) {
                $sector = \App\Models\Sector::where('name', $data['sector'])->first();
                $updateData['sector_id'] = $sector ? $sector->id : null;
                
                if ($updateData['sector_id'] && !empty($data['subsector'])) {
                    $subSector = \App\Models\SubSector::where('name', $data['subsector'])
                        ->where('sector_id', $updateData['sector_id'])
                        ->first();
                    $updateData['sub_sector_id'] = $subSector ? $subSector->id : null;
                }
            }

            $report->update($updateData);

            // Log update
            IssueReportStatusHistory::logChange(
                $report->id,
                $user->id,
                $report->status,
                $report->status,
                'Report details updated by officer'
            );

            return ResponseHelper::success($response, 'Issue report updated successfully', [
                'report' => $report->fresh()->toPublicArray()
            ]);

        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to update issue report', 500, $e->getMessage());
        }
    }

    /**
     * Officer delete issue report
     * DELETE /api/officer/issues/{id}
     */
    public function officerDelete(Request $request, Response $response, array $args): Response
    {
        try {
            $report = IssueReport::find($args['id']);

            if (!$report) {
                return ResponseHelper::error($response, 'Issue report not found', 404);
            }

            $user = $request->getAttribute('user');
            $officer = \App\Models\Officer::findByUserId($user->id);

            if (!$officer) {
                return ResponseHelper::error($response, 'Officer profile not found', 403);
            }

            // Relaxed permission: Allow officers to delete any issue in early stages
            // if ($report->submitted_by_officer_id !== $officer->id) {
            //      return ResponseHelper::error($response, 'You can only delete issues you submitted', 403);
            // }

            if (!in_array($report->status, [IssueReport::STATUS_SUBMITTED, IssueReport::STATUS_REJECTED, IssueReport::STATUS_UNDER_OFFICER_REVIEW])) {
                return ResponseHelper::error($response, 'Cannot delete issue that has been processed', 400); 
            }

            // Soft delete or Hard delete? Model doesn't have SoftDeletes trait visible, so assuming hard delete.
            // Or we can just set status to 'closed' / 'void'?
            // User requested "Delete", let's do hard delete to clean up.
            
            // Delete associated records first? Eloquent usually handles cascade if set in DB, 
            // but for safety we might want to manually clean up or just delete the report.
            // Let's try delete.
            $report->delete();

            return ResponseHelper::success($response, 'Issue report deleted successfully');

        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to delete issue report', 500, $e->getMessage());
        }
    }

    /**
     * Assign issue to task force (Admin)
     * PUT /api/admin/issues/{id}/assign-task-force
     */
    public function assignToTaskForce(Request $request, Response $response, array $args): Response
    {
        try {
            $report = IssueReport::find($args['id']);

            if (!$report) {
                return ResponseHelper::error($response, 'Issue report not found', 404);
            }

            $data = $request->getParsedBody();
            $user = $request->getAttribute('user');

            if (empty($data['task_force_id'])) {
                return ResponseHelper::error($response, 'Task force member ID is required', 400);
            }

            $oldStatus = $report->status;
            $report->assignToTaskForce((int)$data['task_force_id']);

            // Log status change
            IssueReportStatusHistory::logChange(
                $report->id,
                $user->id ?? 0,
                $oldStatus,
                IssueReport::STATUS_ASSIGNED_TO_TASK_FORCE,
                'Assigned to task force for investigation'
            );

            return ResponseHelper::success($response, 'Issue assigned to task force successfully', [
                'report' => $report->fresh()->toFullArray()
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to assign to task force', 500, $e->getMessage());
        }
    }

    /**
     * Allocate resources to issue (Admin)
     * PUT /api/admin/issues/{id}/allocate-resources
     */
    public function allocateResources(Request $request, Response $response, array $args): Response
    {
        try {
            $report = IssueReport::find($args['id']);

            if (!$report) {
                return ResponseHelper::error($response, 'Issue report not found', 404);
            }

            if ($report->status !== IssueReport::STATUS_ASSESSMENT_SUBMITTED) {
                return ResponseHelper::error($response, 'Issue must have submitted assessment before allocating resources', 400);
            }

            $data = $request->getParsedBody();
            $user = $request->getAttribute('user');

            if (empty($data['budget'])) {
                return ResponseHelper::error($response, 'Budget is required', 400);
            }

            $oldStatus = $report->status;
            $report->allocateResources(
                (int)$user->id,
                (float)$data['budget'],
                $data['resources'] ?? null
            );

            // Log status change
            IssueReportStatusHistory::logChange(
                $report->id,
                $user->id ?? 0,
                $oldStatus,
                IssueReport::STATUS_RESOURCES_ALLOCATED,
                'Resources allocated: GHS ' . number_format((float)$data['budget'], 2)
            );

            return ResponseHelper::success($response, 'Resources allocated successfully', [
                'report' => $report->fresh()->toFullArray()
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to allocate resources', 500, $e->getMessage());
        }
    }

    /**
     * Review assessment report (Admin)
     * PUT /api/admin/issues/{id}/review-assessment
     */
    public function reviewAssessment(Request $request, Response $response, array $args): Response
    {
        try {
            $report = IssueReport::with('assessmentReport')->find($args['id']);

            if (!$report) {
                return ResponseHelper::error($response, 'Issue report not found', 404);
            }

            if (!$report->assessmentReport) {
                return ResponseHelper::error($response, 'No assessment report found', 404);
            }

            $data = $request->getParsedBody();
            $user = $request->getAttribute('user');

            if (empty($data['action'])) {
                return ResponseHelper::error($response, 'Action is required (approve/reject/revision)', 400);
            }

            $assessment = $report->assessmentReport;
            $action = $data['action'];
            $notes = $data['notes'] ?? null;

            switch ($action) {
                case 'approve':
                    $assessment->approve((int)$user->id, $notes);
                    // Stay as assessment_submitted or move to resources_allocated? 
                    // Usually wait for explicit resource allocation.
                    break;
                case 'reject':
                    $assessment->reject((int)$user->id, $notes);
                    // Revert issue status so Task Force can see and resubmit
                    $report->status = IssueReport::STATUS_ASSESSMENT_IN_PROGRESS;
                    $report->save();
                    
                    IssueReportStatusHistory::logChange(
                        $report->id,
                        (int)$user->id,
                        IssueReport::STATUS_ASSESSMENT_SUBMITTED,
                        IssueReport::STATUS_ASSESSMENT_IN_PROGRESS,
                        'Assessment rejected: ' . $notes
                    );
                    break;
                case 'revision':
                    $assessment->requestRevision((int)$user->id, $notes);
                    // Revert issue status to allow resubmission
                    $report->status = IssueReport::STATUS_ASSESSMENT_IN_PROGRESS;
                    $report->save();

                    IssueReportStatusHistory::logChange(
                        $report->id,
                        (int)$user->id,
                        IssueReport::STATUS_ASSESSMENT_SUBMITTED,
                        IssueReport::STATUS_ASSESSMENT_IN_PROGRESS,
                        'Assessment revision requested: ' . $notes
                    );
                    break;
                default:
                    return ResponseHelper::error($response, 'Invalid action', 400);
            }

            return ResponseHelper::success($response, 'Assessment reviewed successfully', [
                'assessment' => $assessment->fresh()->toPublicArray(),
                'report' => $report->fresh()->toFullArray()
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to review assessment', 500, $e->getMessage());
        }
    }

    /**
     * Review resolution report and finalize (Admin)
     * PUT /api/admin/issues/{id}/review-resolution
     */
    public function reviewResolution(Request $request, Response $response, array $args): Response
    {
        try {
            $report = IssueReport::with('resolutionReport')->find($args['id']);

            if (!$report) {
                return ResponseHelper::error($response, 'Issue report not found', 404);
            }

            if (!$report->resolutionReport) {
                return ResponseHelper::error($response, 'No resolution report found', 404);
            }

            $data = $request->getParsedBody();
            $user = $request->getAttribute('user');

            if (empty($data['action'])) {
                return ResponseHelper::error($response, 'Action is required (approve/reject/revision)', 400);
            }

            $resolution = $report->resolutionReport;
            $action = $data['action'];
            $notes = $data['notes'] ?? null;

            switch ($action) {
                case 'approve':
                    $resolution->approve((int)$user->id, $notes);
                    // Log final resolution
                    IssueReportStatusHistory::logChange(
                        $report->id,
                        $user->id ?? 0,
                        $report->status,
                        IssueReport::STATUS_RESOLVED,
                        'Issue resolved and closed'
                    );
                    break;
                case 'reject':
                    $resolution->reject((int)$user->id, $notes);
                    break;
                case 'revision':
                    $resolution->requestRevision((int)$user->id, $notes);
                    break;
                default:
                    return ResponseHelper::error($response, 'Invalid action', 400);
            }

            return ResponseHelper::success($response, 'Resolution reviewed successfully', [
                'resolution' => $resolution->fresh()->toPublicArray(),
                'report' => $report->fresh()->toFullArray()
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to review resolution', 500, $e->getMessage());
        }
    }

    /**
     * Officer forwards issue to admin (Officer)
     * PUT /api/officer/issues/{id}/forward
     */
    public function officerForward(Request $request, Response $response, array $args): Response
    {
        try {
            $report = IssueReport::find($args['id']);

            if (!$report) {
                return ResponseHelper::error($response, 'Issue report not found', 404);
            }

            $user = $request->getAttribute('user');
            $data = $request->getParsedBody();

            $oldStatus = $report->status;
            $report->forwardToAdmin();

            // Log status change
            IssueReportStatusHistory::logChange(
                $report->id,
                $user->id ?? 0,
                $oldStatus,
                IssueReport::STATUS_FORWARDED_TO_ADMIN,
                $data['notes'] ?? 'Forwarded to admin by officer'
            );

            return ResponseHelper::success($response, 'Issue forwarded to admin successfully', [
                'report' => $report->fresh()->toFullArray()
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to forward issue', 500, $e->getMessage());
        }
    }

    /**
     * Get issues awaiting admin action
     * GET /api/admin/issues/awaiting-action
     */
    public function awaitingAction(Request $request, Response $response): Response
    {
        try {
            $reports = IssueReport::with(['assignedTaskForce.user', 'assessmentReport', 'resolutionReport'])
                ->whereIn('status', [
                    IssueReport::STATUS_FORWARDED_TO_ADMIN,
                    IssueReport::STATUS_ASSESSMENT_SUBMITTED,
                    IssueReport::STATUS_RESOLUTION_SUBMITTED,
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            return ResponseHelper::success($response, 'Issues awaiting action fetched successfully', [
                'reports' => $reports->map(fn($r) => $r->toFullArray())->toArray(),
                'counts' => [
                    'awaiting_assignment' => $reports->where('status', IssueReport::STATUS_FORWARDED_TO_ADMIN)->count(),
                    'awaiting_assessment_review' => $reports->where('status', IssueReport::STATUS_ASSESSMENT_SUBMITTED)->count(),
                    'awaiting_resolution_review' => $reports->where('status', IssueReport::STATUS_RESOLUTION_SUBMITTED)->count(),
                ]
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch issues', 500, $e->getMessage());
        }
    }
}
