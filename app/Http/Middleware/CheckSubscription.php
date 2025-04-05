<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSubscription
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        $subscription = $user->subscription()
            ->where('end_date', '>', now())
            ->where('status', 'active')
            ->first();

        if (!$subscription) {
            return response()->json([
                'message' => 'Bạn cần có gói thành viên để thực hiện chức năng này',
                'code' => 'NO_SUBSCRIPTION'
            ], 403);
        }

        return $next($request);
    }
} 