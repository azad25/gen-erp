<?php

namespace App\Http\Controllers\Api\Public;

use App\Domain\CMS\Contracts\ContactServiceInterface;
use App\Domain\CMS\Contracts\PublicSiteServiceInterface;
use App\Domain\CMS\DTOs\ContactSubmissionData;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Public controller for contact form submissions.
 */
#[OA\Tag(name: 'Public - Contact Forms', description: 'Public endpoints for contact form submissions')]
class ContactController extends Controller
{
    public function __construct(
        private readonly ContactServiceInterface $contactService,
        private readonly PublicSiteServiceInterface $publicSiteService
    ) {}

    /**
     * Submit a contact form.
     */
    #[OA\Post(
        path: '/api/public/{tenant}/contact',
        summary: 'Submit contact form',
        description: 'Submit a contact form for a site',
        tags: ['Public - Contact Forms'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                description: 'Site subdomain or custom domain',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', description: 'Contact name'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', description: 'Contact email'),
                    new OA\Property(property: 'phone', type: 'string', description: 'Contact phone (optional)'),
                    new OA\Property(property: 'company', type: 'string', description: 'Company name (optional)'),
                    new OA\Property(property: 'subject', type: 'string', description: 'Message subject (optional)'),
                    new OA\Property(property: 'message', type: 'string', description: 'Message content'),
                    new OA\Property(property: 'form_data', type: 'object', description: 'Additional form fields (optional)'),
                    new OA\Property(property: 'source', type: 'string', description: 'Form source (optional)', default: 'contact_form')
                ],
                required: ['name', 'email', 'message']
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Contact form submitted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Thank you for your message. We will get back to you soon.'),
                        new OA\Property(property: 'submission_id', type: 'integer', description: 'Submission ID for reference')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Site not found'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            ),
            new OA\Response(
                response: 429,
                description: 'Too many requests'
            )
        ]
    )]
    public function submit(Request $request, string $tenant): JsonResponse
    {
        // Find the site
        $site = $this->publicSiteService->findSiteByTenant($tenant);
        
        if (!$site) {
            return response()->json(['message' => 'Site not found'], 404);
        }

        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
            'form_data' => 'nullable|array',
            'source' => 'nullable|string|max:50',
        ]);

        // Rate limiting check (simple implementation)
        $recentSubmissions = \App\Domain\CMS\Models\ContactSubmission::where('email', $validated['email'])
            ->where('created_at', '>', now()->subMinutes(5))
            ->count();

        if ($recentSubmissions >= 3) {
            return response()->json([
                'message' => 'Too many submissions. Please wait before submitting again.'
            ], 429);
        }

        // Create submission data
        $submissionData = new ContactSubmissionData(
            siteId: $site->id,
            name: $validated['name'],
            email: $validated['email'],
            phone: $validated['phone'] ?? null,
            company: $validated['company'] ?? null,
            subject: $validated['subject'] ?? null,
            message: $validated['message'],
            formData: $validated['form_data'] ?? null,
            source: $validated['source'] ?? 'contact_form',
            ipAddress: $request->ip(),
            userAgent: $request->userAgent()
        );

        // Submit the form
        $submission = $this->contactService->submitContactForm($submissionData);

        return response()->json([
            'message' => 'Thank you for your message. We will get back to you soon.',
            'submission_id' => $submission->id,
        ], 201);
    }

    /**
     * Subscribe to newsletter.
     */
    #[OA\Post(
        path: '/api/public/{tenant}/newsletter',
        summary: 'Subscribe to newsletter',
        description: 'Subscribe to site newsletter',
        tags: ['Public - Contact Forms'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                description: 'Site subdomain or custom domain',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', description: 'Subscriber email'),
                    new OA\Property(property: 'name', type: 'string', description: 'Subscriber name (optional)')
                ],
                required: ['email']
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Newsletter subscription successful'
            ),
            new OA\Response(
                response: 404,
                description: 'Site not found'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            )
        ]
    )]
    public function newsletter(Request $request, string $tenant): JsonResponse
    {
        // Find the site
        $site = $this->publicSiteService->findSiteByTenant($tenant);
        
        if (!$site) {
            return response()->json(['message' => 'Site not found'], 404);
        }

        // Validate the request
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'name' => 'nullable|string|max:255',
        ]);

        // Check if already subscribed
        $existingSubscription = \App\Domain\CMS\Models\ContactSubmission::where('site_id', $site->id)
            ->where('email', $validated['email'])
            ->where('source', 'newsletter')
            ->first();

        if ($existingSubscription) {
            return response()->json([
                'message' => 'You are already subscribed to our newsletter.',
            ], 200);
        }

        // Create newsletter subscription
        $submissionData = new ContactSubmissionData(
            siteId: $site->id,
            name: $validated['name'] ?? 'Newsletter Subscriber',
            email: $validated['email'],
            message: 'Newsletter subscription',
            source: 'newsletter',
            ipAddress: $request->ip(),
            userAgent: $request->userAgent()
        );

        $this->contactService->submitContactForm($submissionData);

        return response()->json([
            'message' => 'Thank you for subscribing to our newsletter!',
        ], 201);
    }
}