<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DigitalProduct;
use App\Models\DigitalProductFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Admin management of the sellable files. Files are stored STRICTLY on the
 * `local` (private) disk rooted at storage/app/private — never on `public`.
 * They are therefore physically unreachable by a direct URL and can only be
 * served through the signed-download flow.
 */
class DigitalProductFileController extends Controller
{
    /** POST /api/dashboard/digital-products/{product}/files */
    public function store(Request $request, DigitalProduct $product)
    {
        $request->validate([
            'files'   => 'required|array|min:1',
            'files.*' => 'file|max:51200|mimes:pdf,epub,zip,doc,docx,ppt,pptx,xls,xlsx,png,jpg,jpeg', // 50MB each
        ]);

        $created = [];

        foreach ($request->file('files') as $file) {
            // PRIVATE vault. Path is relative to storage/app/private.
            $path = $file->store("digital_products/{$product->id}", 'local');

            $created[] = DigitalProductFile::create([
                'digital_product_id' => $product->id,
                'file_name'          => $file->getClientOriginalName(),
                'file_path'          => $path,
                'file_size'          => $file->getSize(),
            ]);
        }

        return response()->json($created, 201);
    }

    /** DELETE /api/dashboard/digital-product-files/{file} */
    public function destroy(DigitalProductFile $file)
    {
        Storage::disk('local')->delete($file->file_path);
        $file->delete();

        return response()->json(['message' => 'File deleted']);
    }
}
