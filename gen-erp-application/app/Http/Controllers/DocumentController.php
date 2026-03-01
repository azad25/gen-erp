<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentService $documentService,
    ) {}

    /**
     * Download a document with signed URL.
     */
    public function download(Request $request, int $document)
    {
        $doc = Document::withoutGlobalScopes()->findOrFail($document);

        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $contents = $this->documentService->getContents($doc);
        if (! $contents) {
            abort(404);
        }

        return Response::make($contents, 200, [
            'Content-Type' => $doc->mime_type,
            'Content-Disposition' => 'attachment; filename="' . $doc->name . '"',
        ]);
    }

    /**
     * Get a thumbnail for a document.
     */
    public function thumbnail(Request $request, int $document)
    {
        $doc = Document::withoutGlobalScopes()->findOrFail($document);

        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $thumbPath = "thumbnails/{$document}_thumb." . $doc->extension();
        $fullThumbPath = storage_path("app/private/{$thumbPath}");

        if (! file_exists($fullThumbPath)) {
            abort(404);
        }

        $contents = file_get_contents($fullThumbPath);

        return Response::make($contents, 200, [
            'Content-Type' => $doc->mime_type,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Preview a document inline.
     */
    public function preview(Request $request, int $document)
    {
        $doc = Document::withoutGlobalScopes()->findOrFail($document);

        if (! $request->hasValidSignature()) {
            abort(403);
        }

        if (! $doc->isPreviewable()) {
            abort(403, 'This file type cannot be previewed.');
        }

        $contents = $this->documentService->getContents($doc);
        if (! $contents) {
            abort(404);
        }

        return Response::make($contents, 200, [
            'Content-Type' => $doc->mime_type,
            'Content-Disposition' => 'inline; filename="' . $doc->name . '"',
        ]);
    }
}
