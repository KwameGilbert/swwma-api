<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Sector;
use App\Models\WebAdmin;
use App\Helper\ResponseHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;

/**
 * SectorController
 * 
 * Handles CRUD operations for project sectors/categories.
 * Web Admins only.
 */
class SectorController
{
    /**
     * Get all active sectors (Public)
     * GET /api/sectors
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $query = Sector::active()->withCount('projects');

            if (!empty($queryParams['category_id'])) {
                $query->where('category_id', $queryParams['category_id']);
            }

            $sectors = $query->get();

            return ResponseHelper::success($response, 'Sectors fetched successfully', [
                'sectors' => $sectors->map(fn($s) => $s->toPublicArray())->toArray()
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch sectors', 500, $e->getMessage());
        }
    }

    /**
     * Get single sector by slug with projects (Public)
     * GET /api/sectors/{slug}
     */
    public function showBySlug(Request $request, Response $response, array $args): Response
    {
        try {
            $sector = Sector::with('projects')->where('slug', $args['slug'])->first();

            if (!$sector) {
                return ResponseHelper::error($response, 'Sector not found', 404);
            }

            return ResponseHelper::success($response, 'Sector fetched successfully', [
                'sector' => array_merge($sector->toPublicArray(), [
                    'projects' => $sector->projects->map(fn($p) => $p->toPublicArray())->toArray()
                ])
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch sector', 500, $e->getMessage());
        }
    }

    /**
     * Get all sectors including inactive (Admin)
     * GET /api/admin/sectors
     */
    public function adminIndex(Request $request, Response $response): Response
    {
        try {
            $sectors = Sector::orderBy('display_order')->withCount('projects')->get();

            return ResponseHelper::success($response, 'Sectors fetched successfully', [
                'sectors' => $sectors->toArray()
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch sectors', 500, $e->getMessage());
        }
    }

    /**
     * Get single sector (Admin)
     * GET /api/admin/sectors/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $sector = Sector::withCount('projects')->find($args['id']);

            if (!$sector) {
                return ResponseHelper::error($response, 'Sector not found', 404);
            }

            return ResponseHelper::success($response, 'Sector fetched successfully', [
                'sector' => $sector->toArray()
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch sector', 500, $e->getMessage());
        }
    }

    /**
     * Create new sector
     * POST /api/admin/sectors
     */
    public function store(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            $user = $request->getAttribute('user');

            // Validation
            if (empty($data['name'])) {
                return ResponseHelper::error($response, 'Name is required', 400);
            }

            // Generate slug
            $slug = $data['slug'] ?? $this->generateSlug($data['name']);
            if (Sector::where('slug', $slug)->exists()) {
                $slug = $slug . '-' . time();
            }

            // Fetch the web-admin profile for this user
            $webAdmin = $user ? WebAdmin::findByUserId($user->id) : null;

            $sector = Sector::create([
                'created_by' => $webAdmin->id ?? null,
                'category_id' => $data['category_id'] ?? null,
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'icon' => $data['icon'] ?? null,
                'color' => $data['color'] ?? null,
                'display_order' => $data['display_order'] ?? 0,
                'status' => $data['status'] ?? Sector::STATUS_ACTIVE,
            ]);

            return ResponseHelper::success($response, 'Sector created successfully', [
                'sector' => $sector->toArray()
            ], 201);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to create sector', 500, $e->getMessage());
        }
    }

    /**
     * Update sector
     * PUT /api/admin/sectors/{id}
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $sector = Sector::find($args['id']);

            if (!$sector) {
                return ResponseHelper::error($response, 'Sector not found', 404);
            }

            $data = $request->getParsedBody();
            $user = $request->getAttribute('user');

            // Fetch the web-admin profile for this user
            $webAdmin = $user ? WebAdmin::findByUserId($user->id) : null;

            $sector->update([
                'updated_by' => $webAdmin->id ?? null,
                'category_id' => $data['category_id'] ?? $sector->category_id,
                'name' => $data['name'] ?? $sector->name,
                'slug' => $data['slug'] ?? $sector->slug,
                'description' => $data['description'] ?? $sector->description,
                'icon' => $data['icon'] ?? $sector->icon,
                'color' => $data['color'] ?? $sector->color,
                'display_order' => $data['display_order'] ?? $sector->display_order,
                'status' => $data['status'] ?? $sector->status,
            ]);

            return ResponseHelper::success($response, 'Sector updated successfully', [
                'sector' => $sector->fresh()->toArray()
            ]);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to update sector', 500, $e->getMessage());
        }
    }

    /**
     * Delete sector
     * DELETE /api/admin/sectors/{id}
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        try {
            $sector = Sector::withCount('projects')->find($args['id']);

            if (!$sector) {
                return ResponseHelper::error($response, 'Sector not found', 404);
            }

            if ($sector->projects_count > 0) {
                return ResponseHelper::error($response, 'Cannot delete sector with existing projects', 400);
            }

            $sector->delete();

            return ResponseHelper::success($response, 'Sector deleted successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to delete sector', 500, $e->getMessage());
        }
    }

    /**
     * Reorder sectors
     * PUT /api/admin/sectors/reorder
     */
    public function reorder(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();

            if (empty($data['order']) || !is_array($data['order'])) {
                return ResponseHelper::error($response, 'Order array is required', 400);
            }

            foreach ($data['order'] as $index => $sectorId) {
                Sector::where('id', $sectorId)->update(['display_order' => $index]);
            }

            return ResponseHelper::success($response, 'Sectors reordered successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to reorder sectors', 500, $e->getMessage());
        }
    }

    /**
     * Generate URL-friendly slug
     */
    private function generateSlug(string $name): string
    {
        $slug = strtolower($name);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        return trim($slug, '-');
    }
}
