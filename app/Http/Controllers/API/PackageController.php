<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\JsonResponse;

class PackageController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $packages = Package::all();
            return response()->json($packages);
        } catch (\Exception $e) {
            \Log::error('Error fetching packages: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching packages'], 500);
        }
    }

    public function show($id): JsonResponse
    {
        $package = Package::findOrFail($id);
        $package->features = $this->getFeatures($package);

        return response()->json([
            'status' => 'success',
            'data' => $package
        ]);
    }

    private function getFeatures($package): array
    {
        $features = [
            "Mượn tối đa {$package->max_borrows} cuốn sách",
            "Thời gian mượn {$package->borrow_duration} ngày",
        ];

        if ($package->extension_limit > 0) {
            $features[] = $package->extension_limit === -1 
                ? "Gia hạn không giới hạn"
                : "Gia hạn {$package->extension_limit} lần";
        }

        if ($package->can_reserve) {
            $features[] = $package->priority_support 
                ? "Ưu tiên hỗ trợ"
                : "Đặt trước sách";
        }

        if ($package->delivery) {
            $features[] = "Giao sách tận nơi";
        }

        return $features;
    }
} 