<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\ModuleAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ModuleAttachmentController extends Controller
{
    /** GET /api/dashboard/modules/{id}/attachments */
    public function index($moduleId)
    {
        $module = Module::findOrFail($moduleId);
        return response()->json($module->attachments);
    }

    /** POST /api/dashboard/modules/{id}/attachments */
    public function store(Request $request, $moduleId)
    {
        $module = Module::findOrFail($moduleId);

        $request->validate([
            'file'  => 'required|file|mimes:pdf|max:20480',
            'title' => 'nullable|string|max:255',
        ]);

        $file = $request->file('file');
        $path = $file->store("module_attachments/{$moduleId}", 'public');

        $attachment = ModuleAttachment::create([
            'module_id' => $module->id,
            'title'     => $request->input('title') ?: $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
        ]);

        return response()->json($attachment, 201);
    }

    /** DELETE /api/dashboard/attachments/{id} */
    public function destroy($id)
    {
        $attachment = ModuleAttachment::findOrFail($id);
        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();
        return response()->json(['message' => 'Attachment deleted']);
    }
}
