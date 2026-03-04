<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\CMS\Contracts\ContactServiceInterface;
use App\Domain\CMS\Models\ContactSubmission;
use App\Http\Resources\ContactSubmissionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

/**
 * Admin controller for managing contact submissions.
 */
#[OA\Tag(name: 'CMS - Contact Management', description: 'Admin endpoints for managing contact form submissions')]
class ContactController extends BaseApiController
{
    public function __construct(
        private readonly ContactServiceInterface $contactService
    ) {}

    /**
     * Get contact submissions with filtering and pagination.
     */
    #[OA\Get(
        path: '/api/v1/cms/contacts',
        summary: 'List contact submissions',
        description: 'Get paginated list of contact submissions with filtering options',
        tags: ['CMS - Contact Management'],
        parameters: [
            new OA\Parameter(
                name: 'site_id',
                description: 'Filter by site ID',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'status',
                description: 'Filter by status',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['new', 'contacted', 'resolved', 'spam'])
            ),
            new OA\Parameter(
                name: 'per_page',
                description: 'Items per page (max 100)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Contact submissions retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ContactSubmissionResource')),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks')
                    ]
                )
            )
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'site_id' => 'nullable|integer|exists:cms_sites,id',
            'status' => 'nullable|string|in:new,contacted,resolved,spam',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $this->authorize('viewAny', ContactSubmission::class);

        $siteId = $request->integer('site_id');
        $status = $request->string('status');
        $perPage = $request->integer('per_page', 15);

        $submissions = $this->contactService->getSubmissionsForSite($siteId, $status, $perPage);

        return $this->success(
            ContactSubmissionResource::collection($submissions)->response()->getData(),
            'Contact submissions retrieved successfully'
        );
    }

    /**
     * Get a specific contact submission.
     */
    #[OA\Get(
        path: '/api/v1/cms/contacts/{id}',
        summary: 'Get contact submission',
        description: 'Get a specific contact submission by ID',
        tags: ['CMS - Contact Management'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Contact submission ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Contact submission retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/ContactSubmissionResource')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Contact submission not found'
            )
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $submission = $this->contactService->getSubmission($id);

        if (!$submission) {
            return $this->error('Contact submission not found', 404);
        }

        $this->authorize('view', $submission);

        return $this->success(new ContactSubmissionResource($submission), 'Contact submission retrieved successfully');
    }

    /**
     * Mark submission as contacted.
     */
    #[OA\Post(
        path: '/api/v1/cms/contacts/{id}/contacted',
        summary: 'Mark as contacted',
        description: 'Mark a contact submission as contacted',
        tags: ['CMS - Contact Management'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Contact submission ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'assigned_to', type: 'integer', description: 'User ID to assign to (optional)')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Submission marked as contacted successfully'
            ),
            new OA\Response(
                response: 404,
                description: 'Contact submission not found'
            )
        ]
    )]
    public function markAsContacted(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'assigned_to' => 'nullable|integer|exists:users,id'
        ]);

        $submission = ContactSubmission::findOrFail($id);
        $this->authorize('update', $submission);

        $success = $this->contactService->markAsContacted($id, $request->integer('assigned_to'));

        if (!$success) {
            return $this->error('Contact submission not found', 404);
        }

        return $this->success(null, 'Submission marked as contacted successfully');
    }

    /**
     * Mark submission as resolved.
     */
    #[OA\Post(
        path: '/api/v1/cms/contacts/{id}/resolved',
        summary: 'Mark as resolved',
        description: 'Mark a contact submission as resolved',
        tags: ['CMS - Contact Management'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Contact submission ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'notes', type: 'string', description: 'Resolution notes (optional)')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Submission marked as resolved successfully'
            ),
            new OA\Response(
                response: 404,
                description: 'Contact submission not found'
            )
        ]
    )]
    public function markAsResolved(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000'
        ]);

        $submission = ContactSubmission::findOrFail($id);
        $this->authorize('update', $submission);

        $success = $this->contactService->markAsResolved($id, $request->string('notes'));

        if (!$success) {
            return $this->error('Contact submission not found', 404);
        }

        return $this->success(null, 'Submission marked as resolved successfully');
    }

    /**
     * Mark submission as spam.
     */
    #[OA\Post(
        path: '/api/v1/cms/contacts/{id}/spam',
        summary: 'Mark as spam',
        description: 'Mark a contact submission as spam',
        tags: ['CMS - Contact Management'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Contact submission ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Submission marked as spam successfully'
            ),
            new OA\Response(
                response: 404,
                description: 'Contact submission not found'
            )
        ]
    )]
    public function markAsSpam(int $id): JsonResponse
    {
        $submission = ContactSubmission::findOrFail($id);
        $this->authorize('update', $submission);

        $success = $this->contactService->markAsSpam($id);

        if (!$success) {
            return $this->error('Contact submission not found', 404);
        }

        return $this->success(null, 'Submission marked as spam successfully');
    }

    /**
     * Assign submission to a user.
     */
    #[OA\Post(
        path: '/api/v1/cms/contacts/{id}/assign',
        summary: 'Assign submission',
        description: 'Assign a contact submission to a user',
        tags: ['CMS - Contact Management'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Contact submission ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer', description: 'User ID to assign to')
                ],
                required: ['user_id']
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Submission assigned successfully'
            ),
            new OA\Response(
                response: 404,
                description: 'Contact submission not found'
            )
        ]
    )]
    public function assign(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id'
        ]);

        $submission = ContactSubmission::findOrFail($id);
        $this->authorize('update', $submission);

        $success = $this->contactService->assignSubmission($id, $request->integer('user_id'));

        if (!$success) {
            return $this->error('Contact submission not found', 404);
        }

        return $this->success(null, 'Submission assigned successfully');
    }

    /**
     * Get contact statistics.
     */
    #[OA\Get(
        path: '/api/v1/cms/contacts/statistics',
        summary: 'Get contact statistics',
        description: 'Get comprehensive contact submission statistics for a site',
        tags: ['CMS - Contact Management'],
        parameters: [
            new OA\Parameter(
                name: 'site_id',
                description: 'Site ID to get statistics for',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Contact statistics retrieved successfully'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            )
        ]
    )]
    public function statistics(Request $request): JsonResponse
    {
        $request->validate([
            'site_id' => 'required|integer|exists:cms_sites,id'
        ]);

        $this->authorize('viewAny', ContactSubmission::class);

        $statistics = $this->contactService->getContactStatistics($request->integer('site_id'));

        return $this->success($statistics, 'Contact statistics retrieved successfully');
    }

    /**
     * Delete a contact submission.
     */
    #[OA\Delete(
        path: '/api/v1/cms/contacts/{id}',
        summary: 'Delete contact submission',
        description: 'Delete a contact submission',
        tags: ['CMS - Contact Management'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Contact submission ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Contact submission deleted successfully'
            ),
            new OA\Response(
                response: 404,
                description: 'Contact submission not found'
            )
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $submission = ContactSubmission::findOrFail($id);
        $this->authorize('delete', $submission);

        $success = $this->contactService->deleteSubmission($id);

        if (!$success) {
            return $this->error('Contact submission not found', 404);
        }

        return $this->success(null, 'Contact submission deleted successfully');
    }

    /**
     * Export contact submissions to CSV.
     */
    #[OA\Get(
        path: '/api/v1/cms/contacts/export',
        summary: 'Export contact submissions',
        description: 'Export contact submissions to CSV format',
        tags: ['CMS - Contact Management'],
        parameters: [
            new OA\Parameter(
                name: 'site_id',
                description: 'Site ID to export submissions for',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'status',
                description: 'Filter by status',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['new', 'contacted', 'resolved', 'spam'])
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'CSV file generated successfully',
                content: new OA\MediaType(
                    mediaType: 'text/csv',
                    schema: new OA\Schema(type: 'string')
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            )
        ]
    )]
    public function export(Request $request): Response
    {
        $request->validate([
            'site_id' => 'required|integer|exists:cms_sites,id',
            'status' => 'nullable|string|in:new,contacted,resolved,spam',
        ]);

        $this->authorize('viewAny', ContactSubmission::class);

        $csv = $this->contactService->exportSubmissions(
            $request->integer('site_id'),
            $request->string('status')
        );

        $filename = 'contact-submissions-' . now()->format('Y-m-d-H-i-s') . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}