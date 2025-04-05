<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Illuminate\Support\Facades\Log;

class BookController extends Controller
{
    public function featured()
    {
        try {
            $books = Book::where('featured', true)
                ->with('category')
                ->latest()
                ->take(6)
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $books
            ]);

        } catch (\Exception $e) {
            Log::error('Error in featured books: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Có lỗi xảy ra khi lấy sách nổi bật'
            ], 500);
        }
    }

    public function new()
    {
        try {
            $books = Book::with('category')
                ->latest()
                ->take(6)
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $books
            ]);

        } catch (\Exception $e) {
            Log::error('Error in new books: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Có lỗi xảy ra khi lấy sách mới'
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $query = Book::query()->with('category');

        // Tìm kiếm theo title hoặc author
        if ($search = $request->input('q')) {
            $searchTerm = '%' . trim($search) . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'LIKE', $searchTerm)
                  ->orWhere('author', 'LIKE', $searchTerm)
                  ->orWhere('description', 'LIKE', $searchTerm);
            });
        }

        // Lọc theo category
        if ($categoryId = $request->input('category')) {
            $query->where('category_id', $categoryId);
        }

        // Sắp xếp
        $sortBy = $request->input('sort', 'title');
        $direction = 'asc';
        
        if ($sortBy === 'created_at') {
            $direction = 'desc';
        }

        $query->orderBy($sortBy, $direction);

        // Phân trang
        $perPage = $request->input('per_page', 12);
        $books = $query->paginate($perPage);

        return response()->json($books);
    }

    public function show($id): JsonResponse
    {
        $book = Book::with('category')->findOrFail($id);
        return response()->json($book);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required',
            'author' => 'required',
            'description' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'quantity' => 'required|integer',
            'category_id' => 'required|exists:categories,id'
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/books', $imageName);
            $validated['image'] = 'storage/books/' . $imageName;
        }

        $book = Book::create($validated);
        return response()->json([
            'status' => 'success',
            'data' => $book
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $book = Book::findOrFail($id);
        $validated = $request->validate([
            'title' => 'required',
            'author' => 'required',
            'isbn' => 'required|unique:books,isbn,'.$id,
            'quantity' => 'required|integer',
            'category' => 'required'
        ]);

        $book->update($validated);
        return ResponseFacade::json($book);
    }

    public function destroy($id): JsonResponse
    {
        $book = Book::findOrFail($id);
        $book->delete();
        return ResponseFacade::json(null, Response::HTTP_NO_CONTENT);
    }

    public function search(Request $request): JsonResponse
    {
        $query = Book::query()->with('category');

        if ($search = $request->input('q')) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('author', 'LIKE', "%{$search}%");
            });
        }

        if ($categoryId = $request->input('category')) {
            $query->where('category_id', $categoryId);
        }

        $sortBy = $request->input('sort', 'title');
        $sortDir = $request->input('dir', 'asc');
        
        if (in_array($sortBy, ['title', 'author', 'created_at', 'quantity'])) {
            $query->orderBy($sortBy, $sortDir);
        }

        $perPage = $request->input('per_page', 12);
        $books = $query->paginate($perPage);

        return response()->json($books);
    }
} 