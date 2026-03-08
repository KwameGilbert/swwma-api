<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Project;
use App\Models\ConstituencyEvent;
use App\Helper\ResponseHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\ActivityLogService;
use Exception;

/**
 * DevelopmentManagementController
 * 
 * Handles Projects and Constituency Events (Domain 5).
 */
class DevelopmentManagementController
{
    private ActivityLogService $activityLogger;

    public function __construct(ActivityLogService $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    // --- PROJECTS ---

    public function listProjects(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $query = Project::query();
            
            if (!empty($params['status'])) {
                $query->where('status', $params['status']);
            }

            $projects = $query->latest()->get();
            return ResponseHelper::success($response, 'Projects fetched successfully', $projects->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch projects', 500);
        }
    }

    public function showProject(Request $request, Response $response, array $args): Response
    {
        try {
            $project = Project::find($args['id']);
            if (!$project) return ResponseHelper::error($response, 'Project not found', 404);
            return ResponseHelper::success($response, 'Project fetched successfully', $project->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch project', 500);
        }
    }

    public function createProject(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            if (empty($data['title'])) {
                return ResponseHelper::error($response, 'Title is required', 400);
            }
            $project = Project::create($data);

            $this->activityLogger->logCreate(
                $request->getAttribute('user')->id ?? null,
                'Project',
                (int)$project->id,
                $project->toArray()
            );

            return ResponseHelper::success($response, 'Project created successfully', $project->toArray(), 201);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to create project', 500);
        }
    }

    public function updateProject(Request $request, Response $response, array $args): Response
    {
        try {
            $project = Project::find($args['id']);
            if (!$project) return ResponseHelper::error($response, 'Project not found', 404);

            $data = $request->getParsedBody();
            $oldValues = $project->toArray();
            $project->update($data);

            $this->activityLogger->logUpdate(
                $request->getAttribute('user')->id ?? null,
                'Project',
                (int)$project->id,
                $oldValues,
                $project->toArray()
            );

            return ResponseHelper::success($response, 'Project updated successfully', $project->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to update project', 500);
        }
    }

    // --- CONSTITUENCY EVENTS ---

    public function listEvents(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $query = ConstituencyEvent::query();
            
            if (!empty($params['status'])) {
                $query->where('status', $params['status']);
            }

            if (!empty($params['upcoming_only'])) {
                $query->upcoming();
            }

            $events = $query->latest()->get();
            return ResponseHelper::success($response, 'Events fetched successfully', $events->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch events', 500);
        }
    }

    public function createEvent(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            if (empty($data['name']) || empty($data['event_date'])) {
                return ResponseHelper::error($response, 'Name and event_date are required', 400);
            }
            $event = ConstituencyEvent::create($data);

            $this->activityLogger->logCreate(
                $request->getAttribute('user')->id ?? null,
                'ConstituencyEvent',
                (int)$event->id,
                $event->toArray()
            );

            return ResponseHelper::success($response, 'Event created successfully', $event->toArray(), 201);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to create event', 500);
        }
    }

    public function updateEvent(Request $request, Response $response, array $args): Response
    {
        try {
            $event = ConstituencyEvent::find($args['id']);
            if (!$event) return ResponseHelper::error($response, 'Event not found', 404);

            $data = $request->getParsedBody();
            $oldValues = $event->toArray();
            $event->update($data);

            $this->activityLogger->logUpdate(
                $request->getAttribute('user')->id ?? null,
                'ConstituencyEvent',
                (int)$event->id,
                $oldValues,
                $event->toArray()
            );

            return ResponseHelper::success($response, 'Event updated successfully', $event->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to update event', 500);
        }
    }

    public function deleteEvent(Request $request, Response $response, array $args): Response
    {
        try {
            $event = ConstituencyEvent::find($args['id']);
            if (!$event) return ResponseHelper::error($response, 'Event not found', 404);
            $event->delete();
            return ResponseHelper::success($response, 'Event deleted successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to delete event', 500);
        }
    }
}
