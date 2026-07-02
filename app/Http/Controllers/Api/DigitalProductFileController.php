<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DigitalProductFile;
use App\Models\DigitalProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DigitalProductFileController extends Controller
{
    public function store(Request $request, DigitalProduct $product)
    {
        $this->authorize('update', $product);

        $validated = $request->validate([
            'file' => 'required|file|mimes:pdf|max:102400',
            'folder_id' => 'nullable|exists:digital_product_folders,id',
        ]);

        $file = $request->file('file');
        $path = $file->store("digital-products/{$product->id}", 'public');

        $productFile = DigitalProductFile::create([
            'digital_product_id' => $product->id,
            'folder_id' => $validated['folder_id'] ?? null,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
        ]);

        return response()->json([
            'message' => 'File uploaded successfully',
            'data' => $productFile,
        ]);
    }

    public function destroy(DigitalProductFile $file)
    {
        $this->authorize('update', $file->product);

        if (Storage::disk('public')->exists($file->file_path)) {
            Storage::disk('public')->delete($file->file_path);
        }

        $file->delete();

        return response()->json(['message' => 'File deleted successfully']);
    }

    public function listFiles(DigitalProduct $product)
    {
        $this->authorize('view', $product);

        $files = $product->files()
            ->with('folder')
            ->orderBy('order')
            ->get();

        return response()->json($files);
    }

    public function downloadFile(DigitalProductFile $file)
    {
        $enrollment = auth()->user()->enrollments()
            ->where('digital_product_id', $file->digital_product_id)
            ->first();

        if (!$enrollment && !auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!Storage::disk('public')->exists($file->file_path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return Storage::disk('public')->download($file->file_path, $file->file_name);
    }
}
