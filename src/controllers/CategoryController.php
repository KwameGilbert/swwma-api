<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Helper\ResponseHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\ActivityLogService;
use Exception;

class CategoryController
{
    private ActivityLogService $activityLogger;

    public function __construct(ActivityLogService $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    public function index(Request $request, Response $response): Response
    {
        try {
            $categories = Category::with('sectors.subsectors')->get();
            return ResponseHelper::success($response, 'Categories fetched successfully', $categories->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch categories', 500, $e->getMessage());
        }
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $category = Category::with('sectors.subsectors')->find($args['id']);
            if (!$category) {
                return ResponseHelper::error($response, 'Category not found', 404);
            }
            return ResponseHelper::success($response, 'Category fetched successfully', $category->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to fetch category', 500, $e->getMessage());
        }
    }

    public function create(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            if (empty($data['name'])) {
                return ResponseHelper::error($response, 'Name is required', 400);
            }
            $category = Category::create($data);
            
            $this->activityLogger->logCreate(
                $request->getAttribute('user')->id ?? null,
                'Category',
                (int)$category->id,
                $category->toArray()
            );

            return ResponseHelper::success($response, 'Category created successfully', $category->toArray(), 201);
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to create category', 500, $e->getMessage());
        }
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $category = Category::find($args['id']);
            if (!$category) {
                return ResponseHelper::error($response, 'Category not found', 404);
            }
            $data = $request->getParsedBody();
            $oldValues = $category->toArray();
            $category->update($data);
            
            $this->activityLogger->logUpdate(
                $request->getAttribute('user')->id ?? null,
                'Category',
                (int)$category->id,
                $oldValues,
                $category->toArray()
            );

            return ResponseHelper::success($response, 'Category updated successfully', $category->toArray());
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to update category', 500, $e->getMessage());
        }
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $category = Category::find($args['id']);
            if (!$category) {
                return ResponseHelper::error($response, 'Category not found', 404);
            }
            if ($category->sectors()->count() > 0) {
                return ResponseHelper::error($response, 'Cannot delete category with associated sectors', 400);
            }
            
            $oldValues = $category->toArray();
            $categoryId = (int)$category->id;
            $category->delete();

            $this->activityLogger->logDelete(
                $request->getAttribute('user')->id ?? null,
                'Category',
                $categoryId,
                $oldValues
            );

            return ResponseHelper::success($response, 'Category deleted successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($response, 'Failed to delete category', 500, $e->getMessage());
        }
    }
}
