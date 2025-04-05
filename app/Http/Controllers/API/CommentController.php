<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'content' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:comments,id'
        ]);

        $comment = Comment::create([
            'user_id' => auth()->id(),
            'book_id' => $validated['book_id'],
            'parent_id' => $validated['parent_id'] ?? null,
            'content' => $validated['content']
        ]);

        return response()->json($comment->load(['user', 'replies']), 201);
    }

    public function destroy(Comment $comment): JsonResponse
    {
        if ($comment->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->delete();
        return response()->json(['message' => 'Comment deleted successfully']);
    }

    public function bookComments($bookId): JsonResponse
    {
        $comments = Comment::with(['user', 'replies.user'])
            ->where('book_id', $bookId)
            ->whereNull('parent_id') // Chỉ lấy comment gốc
            ->latest()
            ->paginate(10);

        return response()->json($comments);
    }
} 