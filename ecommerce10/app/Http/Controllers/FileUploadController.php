<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileUploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
        ]);

        $file = $request->file('file');
        $path = $file->store('uploads', 'public');

        return response()->json([
            'path' => $path,
            'url' => '/storage/'.$path,
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'extension' => strtolower($file->extension() ?: $file->getClientOriginalExtension()),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'path' => ['required', 'string'],
        ]);

        $path = $validated['path'];

        abort_unless(preg_match('/^uploads\/[A-Za-z0-9._-]+$/', $path) === 1, 422, 'Invalid file path.');

        Storage::disk('public')->delete($path);

        return response()->json([
            'message' => 'File deleted successfully.',
        ]);
    }
}
