<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Issue;
use App\Models\User;
use App\Helper\ResponseHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use Illuminate\Database\Capsule\Manager as DB;


class OfficerDashboardController
{
    /**
     * Get summary statistics for reports dashboard
     * GET /v1/officer/reports/summary
     */
    public function getSummary(Request $request, Response $response): Response
    {
        try {
            $officer = $request->getAttribute('user');

            // For now, officers can see all issues or assigned issues. 
            // We'll show all issues for overall statistics, or those handled by them.
            // Adjust according to business logic.
            $query = Issue::query();

            $totalIssues = $query->count();
            $pendingIssues = (clone $query)->whereIn('status', [Issue::STATUS_SUBMITTED, 'under_review'])->count();
            $resolvedIssues = (clone $query)->where('status', Issue::STATUS_RESOLVED)->count();

            // Avg resolution time could be calculated from IssueResolution/Issue directly, mocking for now
            $avgResolutionTime = 48; 

            return ResponseHelper::success($response, 'Summary fetched', [
                'total_issues' => $totalIssues,
                'pending_issues' => $pendingIssues,
                'resolved_issues' => $resolvedIssues,
                'avg_resolution_time' => $avgResolutionTime,
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch summary', 500, $e->getMessage());
        }
    }

    /**
     * Get issues breakdown by category and location
     * GET /v1/officer/reports/breakdown
     */
    public function getBreakdown(Request $request, Response $response): Response
    {
        try {
            // Category Breakdown
            $categoryBreakdownRaw = Issue::select('category_id', DB::raw('count(*) as total'))
                ->with('category')
                ->groupBy('category_id')
                ->get();
            
            $totalIssues = Issue::count() ?: 1;

            $issuesByCategory = $categoryBreakdownRaw->map(function ($item) use ($totalIssues) {
                return [
                    'name' => $item->category ? $item->category->name : 'Unknown',
                    'count' => $item->total,
                    'percentage' => round(($item->total / $totalIssues) * 100, 1),
                ];
            })->values()->toArray();

            // Location Breakdown
            $locationBreakdownRaw = Issue::select('community_id', DB::raw('count(*) as total'))
                ->with('community')
                ->groupBy('community_id')
                ->get();
            
            $issuesByLocation = $locationBreakdownRaw->map(function ($item) use ($totalIssues) {
                return [
                    'name' => $item->community ? $item->community->name : 'Unknown',
                    'count' => $item->total,
                    'percentage' => round(($item->total / $totalIssues) * 100, 1),
                ];
            })->values()->toArray();

            // Priority Breakdown
            $priorityBreakdownRaw = Issue::select('priority', DB::raw('count(*) as total'))
                ->groupBy('priority')
                ->get();
            
            $issuesByPriority = $priorityBreakdownRaw->mapWithKeys(function ($item) {
                return [$item->priority => $item->total];
            })->toArray();

            return ResponseHelper::success($response, 'Breakdown fetched', [
                'issues_by_category' => $issuesByCategory,
                'issues_by_location' => $issuesByLocation,
                'issues_by_priority' => $issuesByPriority,
                'total' => $totalIssues, // ensure not 0
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch breakdown', 500, $e->getMessage());
        }
    }

    /**
     * Get recent activity feed
     * GET /v1/officer/reports/recent-activity
     */
    public function getRecentActivity(Request $request, Response $response): Response
    {
        try {
            $limit = (int)($request->getQueryParams()['limit'] ?? 10);
            
            $issues = Issue::with(['category', 'agent'])
                ->latest('updated_at')
                ->limit($limit)
                ->get();

            $activities = $issues->map(function ($issue) {
                return [
                    'id' => $issue->id,
                    'case_id' => 'ISS-' . str_pad((string)$issue->id, 5, '0', STR_PAD_LEFT),
                    'title' => $issue->title,
                    'status' => $issue->status,
                    'category' => $issue->category->name ?? 'Unknown',
                    'agent_name' => $issue->agent->email ?? 'System',
                    'updated_at' => $issue->updated_at->toIso8601String(),
                    'formatted_date' => $issue->updated_at->diffForHumans(),
                ];
            });

            return ResponseHelper::success($response, 'Recent activity fetched', [
                'activities' => $activities
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch recent activity', 500, $e->getMessage());
        }
    }

    /**
     * Get monthly trends data for charts
     * GET /v1/officer/reports/trends
     */
    public function getTrends(Request $request, Response $response): Response
    {
        try {
            $months = (int)($request->getQueryParams()['months'] ?? 12);
            $trends = [];
            
            // Mocking data for now; in production group by month from created_at
            $currentMonth = new \DateTime();
            $currentMonth->modify('first day of this month');
            for ($i = $months - 1; $i >= 0; $i--) {
                $monthDate = clone $currentMonth;
                $monthDate->modify("-{$i} months");
                $monthStr = $monthDate->format('M y');
                $trends[] = [
                    'name' => $monthStr,
                    'month' => $monthStr,
                    'total' => rand(10, 50),
                    'resolved' => rand(5, 40),
                ];
            }

            return ResponseHelper::success($response, 'Trends fetched', [
                'trends' => $trends
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch trends', 500, $e->getMessage());
        }
    }

    /**
     * Get status distribution for pie chart
     * GET /v1/officer/reports/status-distribution
     */
    public function getStatusDistribution(Request $request, Response $response): Response
    {
        try {
            $distributionRaw = Issue::select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->get();
            
            $colors = [
                Issue::STATUS_SUBMITTED => '#3b82f6', // blue
                'under_officer_review' => '#f59e0b', // amber
                'forwarded_to_admin' => '#6366f1', // indigo
                'assigned_to_task_force' => '#06b6d4', // cyan
                Issue::STATUS_ASSESSMENT_IN_PROGRESS => '#8b5cf6', // purple
                Issue::STATUS_RESOLVED => '#10b981', // green
                'closed' => '#64748b', // gray
                'rejected' => '#ef4444', // red
            ];

            $distribution = $distributionRaw->map(function ($item) use ($colors) {
                $status = $item->status;
                return [
                    'name' => ucfirst(str_replace('_', ' ', $status)),
                    'status' => $status,
                    'value' => $item->total,
                    'color' => $colors[$status] ?? '#9ca3af', // default gray
                ];
            });

            return ResponseHelper::success($response, 'Status distribution fetched', [
                'distribution' => $distribution
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch status distribution', 500, $e->getMessage());
        }
    }

    /**
     * Get top agent performance metrics
     * GET /v1/officer/reports/agent-performance
     */
    public function getAgentPerformance(Request $request, Response $response): Response
    {
        try {
            $limit = (int)($request->getQueryParams()['limit'] ?? 10);
            
            // For now, mock or select from agents who submitted issues
            $agents = []; // This could query users with role Agent, and count their issues.
            
            return ResponseHelper::success($response, 'Agent performance fetched', [
                'agents' => $agents
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch agent performance', 500, $e->getMessage());
        }
    }

    /**
     * Get officer profile stats for activity overview
     * GET /v1/officer/reports/profile-stats
     */
    public function getProfileStats(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            
            $activity = [
                'total_issues' => Issue::count(),
                'pending_review' => Issue::where('status', Issue::STATUS_SUBMITTED)->count(),
                'resolved' => Issue::where('status', Issue::STATUS_RESOLVED)->count(),
                'active_agents' => User::where('role', User::ROLE_AGENT)->where('status', User::STATUS_ACTIVE)->count(),
            ];

            $officer = [
                'employee_id' => null,
                'department' => null,
                'position' => null,
                'assigned_locations' => null,
                'supervised_agents_count' => User::where('role', User::ROLE_AGENT)->count(),
                'pending_reports_count' => Issue::where('status', Issue::STATUS_SUBMITTED)->count(),
                'permissions' => [
                    'can_manage_projects' => true,
                    'can_manage_reports' => true,
                    'can_manage_events' => true,
                    'can_publish_content' => true,
                ],
            ];

            return ResponseHelper::success($response, 'Profile stats fetched', [
                'activity' => $activity,
                'officer' => $officer,
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch profile stats', 500, $e->getMessage());
        }
    }

    /**
     * Get issues for officer dashboard
     * GET /v1/officer/issues
     */
    public function getOfficerIssues(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $query = Issue::with([
                'constituent', 
                'category', 
                'sector', 
                'subsector', 
                'community', 
                'suburb',
                'agent',
                'officer'
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
            } elseif (!empty($params['category'])) {
                $categoryName = $params['category'];
                $query->whereHas('category', function($q) use ($categoryName) {
                    $q->where('name', $categoryName);
                });
            }

            // Filter by priority
            if (!empty($params['priority'])) {
                $query->where('priority', $params['priority']);
            }

            // Search by title or description
            if (!empty($params['search'])) {
                $search = $params['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $page = max(1, (int)($params['page'] ?? 1));
            $limit = max(1, (int)($params['limit'] ?? 10));
            $offset = ($page - 1) * $limit;

            $total = (clone $query)->count();
            $issues = $query->latest()->skip($offset)->take($limit)->get();

            $reports = $issues->map(function ($issue) {
                return [
                    'id' => $issue->id,
                    'case_id' => 'ISS-' . str_pad((string)$issue->id, 5, '0', STR_PAD_LEFT),
                    'title' => $issue->title,
                    'description' => $issue->description,
                    'category' => $issue->category_name,
                    'community' => $issue->community->name ?? 'Unknown',
                    'suburb' => $issue->suburb->name ?? null,
                    'specific_location' => $issue->specific_location,
                    'status' => $issue->status,
                    'priority' => $issue->priority,
                    'issue_type' => $issue->issue_type,
                    'images' => $issue->images ?? [],
                    'reporter_name' => $issue->constituent->name ?? null,
                    'reporter_phone' => $issue->constituent->phone_number ?? null,
                    'created_at' => $issue->created_at ? $issue->created_at->toIso8601String() : null,
                    'updated_at' => $issue->updated_at ? $issue->updated_at->toIso8601String() : null,
                ];
            });

            return ResponseHelper::success($response, 'Issues fetched successfully', [
                'reports' => $reports,
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch issues', 500, $e->getMessage());
        }
    }

    /**
     * Get dashboard stats for officer
     * GET /v1/officer/dashboard/stats
     */
    public function getStats(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            
            // Total issues (based on what they can see, e.g. all or assigned)
            $totalIssues = Issue::count();
            $pendingIssues = Issue::whereIn('status', [Issue::STATUS_SUBMITTED, 'under_officer_review'])->count();
            $inProgressIssues = Issue::whereIn('status', [
                Issue::STATUS_ASSESSMENT_IN_PROGRESS, 
                Issue::STATUS_RESOLUTION_IN_PROGRESS,
                'forwarded_to_admin',
                'assigned_to_task_force'
            ])->count();
            $resolvedIssues = Issue::where('status', Issue::STATUS_RESOLVED)->count();
            
            // Basic performance / team aggregates
            $activeAgents = User::where('role', User::ROLE_AGENT)->where('status', User::STATUS_ACTIVE)->count();
            $totalAgents = User::where('role', User::ROLE_AGENT)->count();

            return ResponseHelper::success($response, 'Dashboard stats fetched successfully', [
                'my_issues' => [
                    'total' => $totalIssues,
                    'pending_review' => $pendingIssues,
                    'in_progress' => $inProgressIssues,
                    'resolved' => $resolvedIssues,
                ],
                'performance' => [
                    'average_review_time_hours' => 24, // mocked for now
                    'issues_reviewed_this_month' => 15, // mocked for now
                ],
                'team' => [
                    'total_agents' => $totalAgents,
                    'active_agents' => $activeAgents,
                ]
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch dashboard stats', 500, $e->getMessage());
        }
    }
    /**
     * Get details for a specific issue
     * GET /v1/officer/issues/{id}
     */
    public function getIssueDetail(Request $request, Response $response, array $args): Response
    {
        try {
            $issue = Issue::with([
                'constituent', 
                'category', 
                'sector', 
                'subsector', 
                'community', 
                'suburb',
                'agent.agentProfile',
                'officer.officerProfile'
            ])->find($args['id']);

            if (!$issue) {
                return ResponseHelper::error($response, 'Issue not found', 404);
            }

            $mappedIssue = [
                'id' => $issue->id,
                'case_id' => 'ISS-' . str_pad((string)$issue->id, 5, '0', STR_PAD_LEFT),
                'title' => $issue->title,
                'description' => $issue->description,
                'category' => $issue->category->name ?? 'Unknown',
                'category_id' => $issue->category_id,
                'community' => $issue->community->name ?? 'Unknown',
                'community_id' => $issue->community_id,
                'suburb' => $issue->suburb->name ?? null,
                'suburb_id' => $issue->suburb_id,
                'specific_location' => $issue->specific_location,
                'status' => $issue->status,
                'priority' => $issue->priority,
                'issue_type' => $issue->issue_type,
                'sector' => $issue->sector->name ?? null,
                'sector_id' => $issue->sector_id,
                'subsector' => $issue->subsector->name ?? null,
                'sub_sector_id' => $issue->sub_sector_id,
                'people_affected' => $issue->people_affected,
                'estimated_budget' => $issue->estimated_budget,
                'additional_notes' => $issue->details,
                'images' => $issue->images ?? [],
                'reporter_name' => $issue->constituent->name ?? null,
                'reporter_phone' => $issue->constituent->phone_number ?? null,
                'reporter_email' => $issue->constituent->email ?? null,
                'reporter_gender' => $issue->constituent->gender ?? null,
                'reporter_address' => $issue->constituent->home_address ?? null,
                'created_at' => $issue->created_at ? $issue->created_at->toIso8601String() : null,
                'updated_at' => $issue->updated_at ? $issue->updated_at->toIso8601String() : null,
                'agent' => $issue->agent ? [
                    'id' => $issue->agent->id,
                    'name' => $issue->agent->getFullName(),
                    'email' => $issue->agent->email,
                    'phone' => $issue->agent->phone,
                ] : null,
                'assigned_officer' => $issue->officer ? [
                    'id' => $issue->officer->id,
                    'user' => [
                        'name' => $issue->officer->getFullName(),
                        'email' => $issue->officer->email
                    ]
                ] : null,
            ];

            return ResponseHelper::success($response, 'Issue detail fetched', [
                'report' => $mappedIssue
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch issue details', 500, $e->getMessage());
        }
    }
}
