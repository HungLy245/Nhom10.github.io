<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::withCount('books')->get();
        return response()->json($categories);
    }

    public function show($id): JsonResponse
    {
        $category = Category::withCount('books')->findOrFail($id);
        return response()->json($category);
    }

    public function books($id): JsonResponse 
    {
        $category = Category::findOrFail($id);
        $books = $category->books()->with('category')->get();
        return response()->json($books);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories',
        ]);

        $category = Category::create([
            'name' => $validated['name'],
        ]);

        return response()->json($category, 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
        ]);

        $category->update([
            'name' => $validated['name'],
        ]);

        return response()->json($category);
    }

    public function destroy($id): JsonResponse
    {
        $category = Category::findOrFail($id);
        
        // Check if category has books
        if ($category->books()->exists()) {
            return response()->json([
                'message' => 'Cannot delete category that has books'
            ], 409);
        }

        $category->delete();
        return response()->json(null, 204);
    }
} 