<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DigitalProduct;
use Illuminate\Http\Request;

/**
 * Public storefront for digital products. Exposes ONLY marketing-safe fields —
 * file_path is never returned here; the actual files are reachable only through
 * the authenticated, entitlement-gated signed-download flow.
 */
class LandingDigitalProductController extends Controller
{
    /** GET /api/landing/digital-products */
    public function index(Request $request)
    {
        $products = DigitalProduct::where('is_active', true)
            ->withCount('files')
            ->orderByDesc('id')
            ->get(['id', 'title', 'description', 'price', 'thumbnail_path', 'is_free']);

        // If the viewer is logged in, flag which ones they already own so the UI
        // can show "Open in Library" instead of "Buy".
        $ownedIds = [];
        if ($user = auth('sanctum')->user()) {
            $ownedIds = $user->productPurchases()
                ->where('status', 'completed')
                ->pluck('digital_product_id')
                ->all();
        }

        $products->each(fn ($p) => $p->setAttribute('is_owned', in_array($p->id, $ownedIds, true)));

        return response()->json(['products' => $products]);
    }

    /** GET /api/landing/digital-products/{product} */
    public function show(Request $request, DigitalProduct $product)
    {
        abort_unless($product->is_active, 404);

        $product->loadCount('files');
        // Safe file preview: names + sizes only, never paths.
        $files = $product->files()->get(['id', 'file_name', 'file_size']);

        $isOwned = false;
        if ($user = auth('sanctum')->user()) {
            $isOwned = $user->productPurchases()
                ->where('digital_product_id', $product->id)
                ->where('status', 'completed')
                ->exists();
        }

        return response()->json([
            'product' => [
                'id'             => $product->id,
                'title'          => $product->title,
                'description'    => $product->description,
                'price'          => $product->price,
                'thumbnail_path' => $product->thumbnail_path,
                'is_free'        => $product->is_free,
                'files_count'    => $product->files_count,
                'files'          => $files,
                'is_owned'       => $isOwned,
            ],
        ]);
    }
}
