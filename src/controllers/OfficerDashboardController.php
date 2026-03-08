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

            return ResponseHelper::success($response, 'Breakdown fetched', [
                'issues_by_category' => $issuesByCategory,
                'issues_by_location' => $issuesByLocation,
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
                'under_review' => '#f59e0b', // amber
                Issue::STATUS_RESOLVED => '#10b981', // green
                'rejected' => '#ef4444', // red
                Issue::STATUS_ASSESSMENT_IN_PROGRESS => '#8b5cf6', // purple
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

            return ResponseHelper::success($response, 'Issues fetched successfully', [
                'reports' => $issues->toArray(),
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch issues', 500, $e->getMessage());
        }
    }
}
