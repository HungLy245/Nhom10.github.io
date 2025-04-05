<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'rating' => 'required|integer|between:1,5',
            'content' => 'nullable|string|max:1000',
        ]);

        $review = Review::create([
            'user_id' => auth()->id(),
            'book_id' => $validated['book_id'],
            'rating' => $validated['rating'],
            'content' => $validated['content'],
        ]);

        return response()->json($review->load('user'), 201);
    }

    public function bookReviews($bookId): JsonResponse
    {
        $reviews = Review::with('user')
            ->where('book_id', $bookId)
            ->latest()
            ->paginate(10);

        return response()->json($reviews);
    }
} 