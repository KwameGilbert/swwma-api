<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\CommunityIdea;
use App\Models\BlogPost;
use App\Helper\ResponseHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\ActivityLogService;
use Exception;

/**
 * ContentManagementController
 * 
 * Handles Community Ideas and Blog Posts (Domain 4).
 */
class ContentManagementController
{
    private ActivityLogService $activityLogger;

    public function __construct(ActivityLogService $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    // --- COMMUNITY IDEAS ---

    public function listIdeas(Request $request, Response $response): Response
    {
        try {
            $ideas = CommunityIdea::latest()->get();
            return ResponseHelper::success($response, 'Community ideas fetched', $ideas->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch ideas', 500);
        }
    }

    public function createIdea(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            if (empty($data['title']) || empty($data['description'])) {
                return ResponseHelper::error($response, 'Title and description are required', 400);
            }
            $idea = CommunityIdea::create($data);
            return ResponseHelper::success($response, 'Idea submitted successfully', $idea->toArray(), 201);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to submit idea', 500);
        }
    }

    // --- BLOG POSTS ---

    public function listBlogs(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $query = BlogPost::query();
            
            // Publicly only show published
            if (!empty($params['published_only'])) {
                $query->published();
            }

            $blogs = $query->latest()->get();
            return ResponseHelper::success($response, 'Blog posts fetched', $blogs->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch blog posts', 500);
        }
    }

    public function createBlog(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            if (empty($data['title'])) {
                return ResponseHelper::error($response, 'Title is required', 400);
            }
            $blog = BlogPost::create($data);

            $this->activityLogger->logCreate(
                $request->getAttribute('user')->id ?? null,
                'BlogPost',
                (int)$blog->id,
                $blog->toArray()
            );

            return ResponseHelper::success($response, 'Blog post created successfully', $blog->toArray(), 201);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to create blog post', 500);
        }
    }

    public function updateBlog(Request $request, Response $response, array $args): Response
    {
        try {
            $blog = BlogPost::find($args['id']);
            if (!$blog) return ResponseHelper::error($response, 'Blog post not found', 404);

            $data = $request->getParsedBody();
            $oldValues = $blog->toArray();
            $blog->update($data);

            $this->activityLogger->logUpdate(
                $request->getAttribute('user')->id ?? null,
                'BlogPost',
                (int)$blog->id,
                $oldValues,
                $blog->toArray()
            );

            return ResponseHelper::success($response, 'Blog post updated successfully', $blog->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to update blog post', 500);
        }
    }

    public function deleteBlog(Request $request, Response $response, array $args): Response
    {
        try {
            $blog = BlogPost::find($args['id']);
            if (!$blog) return ResponseHelper::error($response, 'Blog post not found', 404);
            $blog->delete();
            return ResponseHelper::success($response, 'Blog post deleted successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to delete blog post', 500);
        }
    }
}
