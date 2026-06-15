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
 * Two-step secure file access (download OR inline view):
 *
 *  1. requestDownload() / requestView()  — auth:sanctum + Policy entitlement check.
 *     Returns a temporary signed URL valid for 5 minutes.
 *
 *  2. serve() / serveInline()  — `signed` middleware only (NO auth).
 *     serve()       → forces a file download (Content-Disposition: attachment).
 *     serveInline() → streams the PDF inline in the browser (Content-Disposition: inline).
 *                     The #toolbar=0 fragment on the frontend hides Chrome's download button.
 */
class DigitalDownloadController extends Controller
{
    /**
     * GET /api/v1/digital-products/{product}/files/{file}/download
     * Entitlement gate — returns a 5-minute signed serve URL (forced download).
     */
    public function requestDownload(Request $request, DigitalProduct $product, DigitalProductFile $file)
    {
        abort_unless($file->digital_product_id === $product->id, 404);

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
     * GET /api/v1/digital-products/{product}/files/{file}/stream
     * Auth-protected PDF stream — react-pdf fetches this directly via axios.
     * Returns the PDF binary inline so the browser (and PDF.js) can render it.
     */
    public function stream(Request $request, DigitalProduct $product, DigitalProductFile $file): BinaryFileResponse
    {
        abort_unless($file->digital_product_id === $product->id, 404);

        $this->authorize('download', $file);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($file->file_path), 404, 'File no longer available.');

        return response()->file(
            $disk->path($file->file_path),
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . rawurlencode($file->file_name) . '"',
                'Cache-Control'       => 'no-store, no-cache, must-revalidate',
            ]
        );
    }

    /**
     * GET /api/v1/digital-products/files/{file}/serve  (named: digital-products.files.serve)
     * Protected by the `signed` middleware. Forces a file download.
     */
    public function serve(Request $request, DigitalProductFile $file): BinaryFileResponse
    {
        $disk = Storage::disk('local');

        abort_unless($disk->exists($file->file_path), 404, 'File no longer available.');

        return response()->download(
            $disk->path($file->file_path),
            $file->file_name,
        );
    }

    /**
     * GET /api/v1/digital-products/files/{file}/view-inline  (named: digital-products.files.view)
     * Protected by the `signed` middleware. Streams the PDF inline — no forced download.
     * The frontend appends #toolbar=0 to suppress Chrome's built-in PDF download button.
     */
    public function serveInline(Request $request, DigitalProductFile $file): BinaryFileResponse
    {
        $disk = Storage::disk('local');

        abort_unless($disk->exists($file->file_path), 404, 'File no longer available.');

        return response()->file(
            $disk->path($file->file_path),
            [
                'Content-Type'              => 'application/pdf',
                'Content-Disposition'       => 'inline; filename="' . rawurlencode($file->file_name) . '"',
                'X-Content-Type-Options'    => 'nosniff',
                'Cache-Control'             => 'no-store, no-cache, must-revalidate',
                // Allow any origin to embed this PDF in an iframe.
                // The signed URL is the authorization — only the rightful owner can generate it.
                'Content-Security-Policy'   => "frame-ancestors *",
            ]
        );
    }
}
