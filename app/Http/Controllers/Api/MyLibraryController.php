<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DigitalProduct;
use Illuminate\Http\Request;

/**
 * Student-facing "My Digital Library" — lists products the authenticated user
 * has a completed purchase for, along with their downloadable files. File paths
 * are NEVER exposed; downloads go through the signed-URL flow.
 */
class MyLibraryController extends Controller
{
    /** GET /api/v1/my-library */
    public function index(Request $request)
    {
        $user = $request->user();

        $productIds = $user->productPurchases()
            ->where('status', 'completed')
            ->pluck('digital_product_id')
            ->unique();

        $products = DigitalProduct::whereIn('id', $productIds)
            ->with(['files:id,digital_product_id,file_name,file_size'])
            ->get(['id', 'title', 'description', 'thumbnail_path', 'price']);

        return response()->json([
            'products' => $products,
        ]);
    }
}
