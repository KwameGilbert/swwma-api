<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\CommunityIdea;
use App\Models\CommunityIdeaVote;
use App\Models\WebAdmin;
use App\Helper\ResponseHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;

/**
 * CommunityIdeaController
 * 
 * Handles CRUD operations for community ideas.
 */
class CommunityIdeaController
{
    /**
     * List all community ideas (admin view)
     * GET /v1/ideas
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $page = (int) ($params['page'] ?? 1);
            $limit = (int) ($params['limit'] ?? 20);
            $status = $params['status'] ?? null;
            $category = $params['category'] ?? null;
            $priority = $params['priority'] ?? null;

            $query = CommunityIdea::query();

            if ($status) {
                $query->where('status', $status);
            }
            if ($category) {
                $query->where('category', $category);
            }
            if ($priority) {
                $query->where('priority', $priority);
            }

            $total = $query->count();
            $ideas = $query
                ->orderBy('votes', 'desc')
                ->orderBy('created_at', 'desc')
                ->offset(($page - 1) * $limit)
                ->limit($limit)
                ->get();

            return ResponseHelper::success($response, 'Ideas retrieved', [
                'ideas' => $ideas->map(fn($idea) => $idea->toFullArray()),
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => (int) ceil($total / $limit),
                ],
                'statistics' => CommunityIdea::getStatistics(),
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to retrieve ideas', 500, $e->getMessage());
        }
    }

    /**
     * Get public community ideas (for public website)
     * GET /v1/ideas/public
     */
    public function publicList(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $page = (int) ($params['page'] ?? 1);
            $limit = (int) ($params['limit'] ?? 20);
            $category = $params['category'] ?? null;

            // Show all ideas except rejected ones publicly to encourage participation
            $query = CommunityIdea::whereIn('status', ['approved', 'implemented', 'pending', 'under_review']);

            if ($category) {
                $query->where('category', $category);
            }

            $total = $query->count();
            $ideas = $query
                ->orderBy('votes', 'desc')
                ->orderBy('created_at', 'desc')
                ->offset(($page - 1) * $limit)
                ->limit($limit)
                ->get();

            return ResponseHelper::success($response, 'Public ideas retrieved', [
                'ideas' => $ideas->map(fn($idea) => $idea->toPublicArray()),
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => (int) ceil($total / $limit),
                ],
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to retrieve ideas', 500, $e->getMessage());
        }
    }

    /**
     * Get a single idea by ID or slug
     * GET /v1/ideas/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $identifier = $args['id'];

            if (is_numeric($identifier)) {
                $idea = CommunityIdea::find((int) $identifier);
            } else {
                $idea = CommunityIdea::findBySlug($identifier);
            }

            if (!$idea) {
                return ResponseHelper::error($response, 'Idea not found', 404);
            }

            return ResponseHelper::success($response, 'Idea retrieved', [
                'idea' => $idea->toFullArray(),
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to retrieve idea', 500, $e->getMessage());
        }
    }

    /**
     * Submit a new community idea (can be done by public or registered users)
     * POST /v1/ideas
     */
    public function store(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            $user = $request->getAttribute('user'); // May be null for public submissions

            // Validate required fields
            if (empty($data['title']) || empty($data['description'])) {
                return ResponseHelper::error($response, 'Title and description are required', 400);
            }

            // For public submissions, require name and email
            if (!$user && (empty($data['submitter_name']) || empty($data['submitter_email']))) {
                return ResponseHelper::error($response, 'Submitter name and email are required for anonymous submissions', 400);
            }

            // Generate slug
            $slug = CommunityIdea::generateSlug($data['title']);

            $idea = CommunityIdea::create([
                'title' => $data['title'],
                'slug' => $slug,
                'description' => $data['description'],
                'category' => $data['category'] ?? 'other',
                'submitter_name' => $user ? $user->name : ($data['submitter_name'] ?? null),
                'submitter_email' => $user ? $user->email : ($data['submitter_email'] ?? null),
                'submitter_contact' => $data['submitter_contact'] ?? null,
                'submitter_user_id' => $user ? $user->id : null,
                'status' => 'pending',
                'priority' => $data['priority'] ?? 'medium',
                'estimated_cost' => $data['estimated_cost'] ?? null,
                'estimated_cost_min' => $data['estimated_cost_min'] ?? null,
                'estimated_cost_max' => $data['estimated_cost_max'] ?? null,
                'location' => $data['location'] ?? null,
                'target_beneficiaries' => $data['target_beneficiaries'] ?? null,
                'implementation_timeline' => $data['implementation_timeline'] ?? null,
                'images' => $data['images'] ?? null,
                'documents' => $data['documents'] ?? null,
            ]);

            return ResponseHelper::success($response, 'Idea submitted successfully', [
                'idea' => $idea->toPublicArray(),
            ], 201);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to submit idea', 500, $e->getMessage());
        }
    }

    /**
     * Update an idea (admin only)
     * PUT /v1/ideas/{id}
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            $data = $request->getParsedBody();

            $idea = CommunityIdea::find($id);
            if (!$idea) {
                return ResponseHelper::error($response, 'Idea not found', 404);
            }

            $updateData = [];

            if (isset($data['title'])) {
                $updateData['title'] = $data['title'];
                if ($data['title'] !== $idea->title) {
                    $updateData['slug'] = CommunityIdea::generateSlug($data['title']);
                }
            }
            if (isset($data['description'])) $updateData['description'] = $data['description'];
            if (isset($data['category'])) $updateData['category'] = $data['category'];
            if (isset($data['priority'])) $updateData['priority'] = $data['priority'];
            if (isset($data['estimated_cost'])) $updateData['estimated_cost'] = $data['estimated_cost'];
            if (isset($data['estimated_cost_min'])) $updateData['estimated_cost_min'] = $data['estimated_cost_min'];
            if (isset($data['estimated_cost_max'])) $updateData['estimated_cost_max'] = $data['estimated_cost_max'];
            if (isset($data['location'])) $updateData['location'] = $data['location'];
            if (isset($data['target_beneficiaries'])) $updateData['target_beneficiaries'] = $data['target_beneficiaries'];
            if (isset($data['implementation_timeline'])) $updateData['implementation_timeline'] = $data['implementation_timeline'];
            if (isset($data['admin_notes'])) $updateData['admin_notes'] = $data['admin_notes'];
            if (isset($data['images'])) $updateData['images'] = $data['images'];
            if (isset($data['documents'])) $updateData['documents'] = $data['documents'];

            $idea->update($updateData);

            return ResponseHelper::success($response, 'Idea updated', [
                'idea' => $idea->fresh()->toFullArray(),
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to update idea', 500, $e->getMessage());
        }
    }

    /**
     * Delete an idea (admin only)
     * DELETE /v1/ideas/{id}
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];

            $idea = CommunityIdea::find($id);
            if (!$idea) {
                return ResponseHelper::error($response, 'Idea not found', 404);
            }

            $idea->delete();

            return ResponseHelper::success($response, 'Idea deleted');
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to delete idea', 500, $e->getMessage());
        }
    }

    /**
     * Change idea status (admin actions)
     * POST /v1/ideas/{id}/status
     */
    public function updateStatus(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            $data = $request->getParsedBody();
            $user = $request->getAttribute('user');

            if (empty($data['status'])) {
                return ResponseHelper::error($response, 'Status is required', 400);
            }

            $idea = CommunityIdea::find($id);
            if (!$idea) {
                return ResponseHelper::error($response, 'Idea not found', 404);
            }

            // Get web admin ID if available, fallback to user ID
            $webAdmin = WebAdmin::findByUserId($user->id);
            $reviewerId = $webAdmin ? $webAdmin->id : ($user->id ?? 1);

            $notes = $data['notes'] ?? $data['admin_notes'] ?? null;

            switch ($data['status']) {
                case 'under_review':
                    $idea->markAsUnderReview($reviewerId);
                    break;
                case 'approved':
                    $idea->approve($reviewerId, $notes);
                    break;
                case 'rejected':
                    $idea->reject($reviewerId, $notes);
                    break;
                case 'implemented':
                    $idea->markAsImplemented();
                    break;
                default:
                    return ResponseHelper::error($response, 'Invalid status', 400);
            }

            return ResponseHelper::success($response, 'Idea status updated', [
                'idea' => $idea->fresh()->toFullArray(),
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to update idea status', 500, $e->getMessage());
        }
    }

    /**
     * Vote for an idea
     * POST /v1/ideas/{id}/vote
     */
    public function vote(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            $user = $request->getAttribute('user');

            $idea = CommunityIdea::find($id);
            if (!$idea) {
                return ResponseHelper::error($response, 'Idea not found', 404);
            }

            // Allow voting on all public ideas (pending, under_review, approved, implemented)
            // Only block voting on rejected ideas
            if ($idea->status === 'rejected') {
                return ResponseHelper::error($response, 'Cannot vote on this idea', 400);
            }

            // Get vote type from request body
            $body = $request->getParsedBody();
            $type = $body['type'] ?? 'up';

            if (!in_array($type, ['up', 'down'])) {
                return ResponseHelper::error($response, 'Invalid vote type', 400);
            }

            // Get IP address for anonymous voting
            $ip = $request->getServerParams()['REMOTE_ADDR'] ?? null;

            $result = CommunityIdeaVote::recordVote(
                $id,
                $user ? $user->id : null,
                $ip,
                $type
            );

            if (!$result || (isset($result['action']) && $result['action'] === 'error')) {
                $message = $result['message'] ?? 'Failed to record vote';
                return ResponseHelper::error($response, $message, 400);
            }

            $message = 'Vote recorded';
            if ($result['action'] === 'removed') {
                $message = 'Vote removed';
            } elseif ($result['action'] === 'switched') {
                $message = 'Vote switched';
            }

            return ResponseHelper::success($response, $message, [
                'idea' => $idea->fresh()->toPublicArray(),
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to record vote', 500, $e->getMessage());
        }
    }

    /**
     * Remove vote from an idea
     * DELETE /v1/ideas/{id}/vote
     */
    public function unvote(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            $user = $request->getAttribute('user');

            if (!$user) {
                return ResponseHelper::error($response, 'Authentication required to remove vote', 401);
            }

            $idea = CommunityIdea::find($id);
            if (!$idea) {
                return ResponseHelper::error($response, 'Idea not found', 404);
            }

            $removed = CommunityIdeaVote::removeVote($id, $user->id);

            if (!$removed) {
                return ResponseHelper::error($response, 'You have not voted for this idea', 400);
            }

            return ResponseHelper::success($response, 'Vote removed', [
                'idea' => $idea->fresh()->toPublicArray(),
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to remove vote', 500, $e->getMessage());
        }
    }

    /**
     * Get top voted ideas
     * GET /v1/ideas/top
     */
    public function topVoted(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $limit = (int) ($params['limit'] ?? 10);

            $ideas = CommunityIdea::whereIn('status', ['approved', 'implemented'])
                ->orderBy('votes', 'desc')
                ->limit($limit)
                ->get();

            return ResponseHelper::success($response, 'Top voted ideas retrieved', [
                'ideas' => $ideas->map(fn($idea) => $idea->toPublicArray()),
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to retrieve top ideas', 500, $e->getMessage());
        }
    }
}
