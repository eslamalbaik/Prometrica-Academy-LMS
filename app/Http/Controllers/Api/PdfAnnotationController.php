<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DigitalProduct;
use App\Models\DigitalProductFile;
use App\Models\PdfAnnotation;
use Illuminate\Http\Request;

class PdfAnnotationController extends Controller
{
    /** GET /api/v1/digital-products/{product}/files/{file}/annotations */
    public function index(Request $request, DigitalProduct $product, DigitalProductFile $file)
    {
        abort_unless($file->digital_product_id === $product->id, 404);
        $this->authorize('download', $file);

        $annotations = PdfAnnotation::where('user_id', $request->user()->id)
            ->where('digital_product_file_id', $file->id)
            ->orderBy('page')
            ->orderBy('created_at')
            ->get();

        return response()->json(['annotations' => $annotations]);
    }

    /** POST /api/v1/digital-products/{product}/files/{file}/annotations */
    public function store(Request $request, DigitalProduct $product, DigitalProductFile $file)
    {
        abort_unless($file->digital_product_id === $product->id, 404);
        $this->authorize('download', $file);

        $data = $request->validate([
            'page'          => 'required|integer|min:1',
            'type'          => 'required|in:highlight,note',
            'selected_text' => 'nullable|string|max:2000',
            'note'          => 'nullable|string|max:2000',
            'color'         => 'nullable|string|max:20',
            'position'      => 'nullable|array',
        ]);

        $annotation = PdfAnnotation::create([
            ...$data,
            'user_id'                 => $request->user()->id,
            'digital_product_file_id' => $file->id,
            'color'                   => $data['color'] ?? 'yellow',
        ]);

        return response()->json(['annotation' => $annotation], 201);
    }

    /** PATCH /api/v1/annotations/{annotation} */
    public function update(Request $request, PdfAnnotation $annotation)
    {
        abort_unless($annotation->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'note'  => 'nullable|string|max:2000',
            'color' => 'nullable|string|max:20',
        ]);

        $annotation->update($data);

        return response()->json(['annotation' => $annotation]);
    }

    /** DELETE /api/v1/annotations/{annotation} */
    public function destroy(Request $request, PdfAnnotation $annotation)
    {
        abort_unless($annotation->user_id === $request->user()->id, 403);
        $annotation->delete();

        return response()->json(['message' => 'Annotation deleted.']);
    }

    /** DELETE /api/v1/digital-products/{product}/files/{file}/annotations */
    public function destroyAll(Request $request, DigitalProduct $product, DigitalProductFile $file)
    {
        abort_unless($file->digital_product_id === $product->id, 404);
        $this->authorize('download', $file);

        $count = PdfAnnotation::where('user_id', $request->user()->id)
            ->where('digital_product_file_id', $file->id)
            ->delete();

        return response()->json(['message' => "Deleted {$count} annotations."]);
    }
}
