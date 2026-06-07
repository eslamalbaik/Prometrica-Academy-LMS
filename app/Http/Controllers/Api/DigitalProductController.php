<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DigitalProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Admin CRUD for digital products. Thumbnails live on the PUBLIC disk (they are
 * marketing images); the actual sellable files live on the PRIVATE disk and are
 * managed by DigitalProductFileController.
 */
class DigitalProductController extends Controller
{
    /** GET /api/dashboard/digital-products */
    public function index()
    {
        $products = DigitalProduct::withCount(['files', 'purchases'])
            ->orderByDesc('id')
            ->paginate(15);

        return response()->json($products);
    }

    /** GET /api/dashboard/digital-products/{product} */
    public function show(DigitalProduct $product)
    {
        $product->load('files');
        $product->loadCount('purchases');

        return response()->json($product);
    }

    /** POST /api/dashboard/digital-products */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'is_active'   => 'boolean',
            'is_free'     => 'boolean',
            'access_days' => 'nullable|integer|min:1',
            'thumbnail'   => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail_path'] = $request->file('thumbnail')
                ->store('digital_product_thumbnails', 'public');
        }

        unset($data['thumbnail']);

        $product = DigitalProduct::create($data);

        return response()->json($product, 201);
    }

    /** POST /api/dashboard/digital-products/{product}  (multipart update) */
    public function update(Request $request, DigitalProduct $product)
    {
        $data = $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'sometimes|required|numeric|min:0',
            'is_active'   => 'boolean',
            'is_free'     => 'boolean',
            'access_days' => 'nullable|integer|min:1',
            'thumbnail'   => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('thumbnail')) {
            if ($product->thumbnail_path) {
                Storage::disk('public')->delete($product->thumbnail_path);
            }
            $data['thumbnail_path'] = $request->file('thumbnail')
                ->store('digital_product_thumbnails', 'public');
        }

        unset($data['thumbnail']);

        $product->update($data);

        return response()->json($product);
    }

    /** DELETE /api/dashboard/digital-products/{product} */
    public function destroy(DigitalProduct $product)
    {
        // Remove private files from the vault first.
        foreach ($product->files as $file) {
            Storage::disk('local')->delete($file->file_path);
        }
        if ($product->thumbnail_path) {
            Storage::disk('public')->delete($product->thumbnail_path);
        }

        $product->delete(); // cascades files + purchases at DB level

        return response()->json(['message' => 'Digital product deleted']);
    }

    /** GET /api/dashboard/digital-products/{product}/sales */
    public function sales(DigitalProduct $product)
    {
        $sales = $product->purchases()
            ->with(['user:id,name,email'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($sales);
    }
}
