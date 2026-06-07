<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DigitalProduct;
use App\Models\DigitalProductFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Two-step secure download:
 *
 *  1. requestDownload()  — auth:sanctum + Policy entitlement check.
 *     Returns a temporary signed URL valid for 5 minutes.
 *
 *  2. serve()            — `signed` middleware only (NO auth). A browser
 *     navigating to the signed URL does not send the Bearer token, so the
 *     signature itself is the authorization. response()->download() streams
 *     the file straight from the private vault.
 */
class DigitalDownloadController extends Controller
{
    /**
     * GET /api/v1/digital-products/{product}/files/{file}/download
     * Entitlement gate — returns a 5-minute signed serve URL.
     */
    public function requestDownload(Request $request, DigitalProduct $product, DigitalProductFile $file)
    {
        // Guard against mismatched product/file pair in the URL.
        abort_unless($file->digital_product_id === $product->id, 404);

        // Policy: admin OR a completed purchase of the parent product.
        $this->authorize('download', $file);

        $url = URL::temporarySignedRoute(
            'digital-products.files.serve',
            now()->addMinutes(5),
            ['file' => $file->id],
        );

        return response()->json([
            'download_url' => $url,
            'file_name'    => $file->file_name,
            'expires_in'   => 300,
        ]);
    }

    /**
     * GET /api/v1/digital-products/files/{file}/serve  (named: digital-products.files.serve)
     * Protected by the `signed` middleware. Streams from the private disk.
     */
    public function serve(Request $request, DigitalProductFile $file): BinaryFileResponse
    {
        $disk = Storage::disk('local');

        abort_unless($disk->exists($file->file_path), 404, 'File no longer available.');

        // Absolute path on the private disk; force a download with the
        // original, human-friendly filename.
        return response()->download(
            $disk->path($file->file_path),
            $file->file_name,
        );
    }
}
