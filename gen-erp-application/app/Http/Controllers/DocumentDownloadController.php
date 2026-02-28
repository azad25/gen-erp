<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\CompanyContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves private document downloads via signed URLs.
 */
class DocumentDownloadController extends Controller
{
    public function __invoke(Request $request, Document $document): BinaryFileResponse
    {
        // Verify company ownership
        if (CompanyContext::hasActive() && $document->company_id !== CompanyContext::activeId()) {
            abort(403, __('Access denied.'));
        }

        $path = storage_path("app/private/{$document->disk_path}");

        if (! file_exists($path)) {
            abort(404, __('File not found.'));
        }

        return response()->download($path, $document->name, [
            'Content-Type' => $document->mime_type,
        ]);
    }
}
