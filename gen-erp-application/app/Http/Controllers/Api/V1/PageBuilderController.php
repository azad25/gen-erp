<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\CMS\Contracts\PageBuilderServiceInterface;
use App\Domain\CMS\Models\Page;
use App\Domain\CMS\Models\Section;
use App\Domain\CMS\Enums\SectionType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Controller for page builder operations.
 */
#[OA\Tag(name: 'CMS - Page Builder', description: 'Page builder interface for drag-and-drop editing')]
class PageBuilderController extends BaseApiController
{
    public function __construct(
        private readonly PageBuilderServiceInterface $pageBuilderService
    ) {}

    /**
     * Get available section types for the page builder.
     */
    #[OA\Get(
        path: '/api/v1/cms/page-builder/section-types',
        summary: 'Get available section types',
        description: 'Get all available section types grouped by category for the page builder',
        tags: ['CMS - Page Builder'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Section types retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            additionalProperties: new OA\Schema(
                                type: 'array',
                                items: new OA\Items(
                                    properties: [
                                        new OA\Property(property: 'value', type: 'string'),
                                        new OA\Property(property: 'label', type: 'string'),
                                        new OA\Property(property: 'category', type: 'string'),
                                        new OA\Property(property: 'icon', type: 'string'),
                                        new OA\Property(property: 'default_content', type: 'object')
                                    ]
                                )
                            )
                        )
                    ]
                )
            )
        ]
    )]
    public function sectionTypes(): JsonResponse
    {
        $sectionTypes = $this->pageBuilderService->getAvailableSectionTypes();
        
        return $this->success($sectionTypes, 'Section types retrieved successfully');
    }

    /**
     * Get page data for the builder.
     */
    #[OA\Get(
        path: '/api/v1/cms/page-builder/pages/{pageId}',
        summary: 'Get page for builder',
        description: 'Get page with sections for the page builder interface',
        tags: ['CMS - Page Builder'],
        parameters: [
            new OA\Parameter(
                name: 'pageId',
                description: 'Page ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Page data retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'page',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer'),
                                        new OA\Property(property: 'title', type: 'string'),
                                        new OA\Property(property: 'slug', type: 'string'),
                                        new OA\Property(property: 'meta_title', type: 'string'),
                                        new OA\Property(property: 'meta_description', type: 'string'),
                                        new OA\Property(property: 'is_published', type: 'boolean'),
                                        new OA\Property(property: 'is_homepage', type: 'boolean')
                                    ]
                                ),
                                new OA\Property(
                                    property: 'sections',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer'),
                                            new OA\Property(property: 'type', type: 'string'),
                                            new OA\Property(property: 'content', type: 'object'),
                                            new OA\Property(property: 'order', type: 'integer'),
                                            new OA\Property(property: 'is_visible', type: 'boolean')
                                        ]
                                    )
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Page not found',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundError')
            )
        ]
    )]
    public function getPage(int $pageId): JsonResponse
    {
        $this->authorize('view', Page::findOrFail($pageId));
        
        $pageData = $this->pageBuilderService->getPageForBuilder($pageId);
        
        return $this->success($pageData, 'Page data retrieved successfully');
    }

    /**
     * Add a section to a page.
     */
    #[OA\Post(
        path: '/api/v1/cms/page-builder/pages/{pageId}/sections',
        summary: 'Add section to page',
        description: 'Add a new section to a page at the specified position',
        tags: ['CMS - Page Builder'],
        parameters: [
            new OA\Parameter(
                name: 'pageId',
                description: 'Page ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'type', type: 'string', description: 'Section type'),
                    new OA\Property(property: 'order', type: 'integer', description: 'Position to insert section (optional)')
                ],
                required: ['type']
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Section added successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/SectionResource'),
                        new OA\Property(property: 'message', type: 'string')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            )
        ]
    )]
    public function addSection(Request $request, int $pageId): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:' . implode(',', array_column(SectionType::cases(), 'value')),
            'order' => 'nullable|integer|min:1'
        ]);

        $page = Page::findOrFail($pageId);
        $this->authorize('update', $page);

        $section = $this->pageBuilderService->addSectionToPage(
            $pageId,
            SectionType::from($request->string('type')),
            $request->integer('order')
        );

        return $this->success($section, 'Section added successfully', 201);
    }

    /**
     * Reorder sections on a page.
     */
    #[OA\Put(
        path: '/api/v1/cms/page-builder/pages/{pageId}/sections/reorder',
        summary: 'Reorder page sections',
        description: 'Reorder sections on a page by providing array of section IDs in desired order',
        tags: ['CMS - Page Builder'],
        parameters: [
            new OA\Parameter(
                name: 'pageId',
                description: 'Page ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'section_ids',
                        type: 'array',
                        items: new OA\Items(type: 'integer'),
                        description: 'Array of section IDs in desired order'
                    )
                ],
                required: ['section_ids']
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sections reordered successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            )
        ]
    )]
    public function reorderSections(Request $request, int $pageId): JsonResponse
    {
        $request->validate([
            'section_ids' => 'required|array',
            'section_ids.*' => 'integer|exists:cms_sections,id'
        ]);

        $page = Page::findOrFail($pageId);
        $this->authorize('update', $page);

        $this->pageBuilderService->reorderSections($pageId, $request->array('section_ids'));

        return $this->success(null, 'Sections reordered successfully');
    }

    /**
     * Update section content.
     */
    #[OA\Put(
        path: '/api/v1/cms/page-builder/sections/{sectionId}/content',
        summary: 'Update section content',
        description: 'Update the content of a specific section',
        tags: ['CMS - Page Builder'],
        parameters: [
            new OA\Parameter(
                name: 'sectionId',
                description: 'Section ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'content', type: 'object', description: 'Section content data')
                ],
                required: ['content']
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Section content updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/SectionResource'),
                        new OA\Property(property: 'message', type: 'string')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            )
        ]
    )]
    public function updateSectionContent(Request $request, int $sectionId): JsonResponse
    {
        $request->validate([
            'content' => 'required|array'
        ]);

        $section = Section::findOrFail($sectionId);
        $this->authorize('update', $section);

        $updatedSection = $this->pageBuilderService->updateSectionContent(
            $sectionId,
            $request->array('content')
        );

        return $this->success($updatedSection, 'Section content updated successfully');
    }

    /**
     * Toggle section visibility.
     */
    #[OA\Post(
        path: '/api/v1/cms/page-builder/sections/{sectionId}/toggle-visibility',
        summary: 'Toggle section visibility',
        description: 'Toggle the visibility of a section',
        tags: ['CMS - Page Builder'],
        parameters: [
            new OA\Parameter(
                name: 'sectionId',
                description: 'Section ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Section visibility toggled successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/SectionResource'),
                        new OA\Property(property: 'message', type: 'string')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Section not found',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundError')
            )
        ]
    )]
    public function toggleSectionVisibility(int $sectionId): JsonResponse
    {
        $section = Section::findOrFail($sectionId);
        $this->authorize('update', $section);

        $updatedSection = $this->pageBuilderService->toggleSectionVisibility($sectionId);

        return $this->success($updatedSection, 'Section visibility toggled successfully');
    }

    /**
     * Duplicate a section.
     */
    #[OA\Post(
        path: '/api/v1/cms/page-builder/sections/{sectionId}/duplicate',
        summary: 'Duplicate section',
        description: 'Create a copy of an existing section',
        tags: ['CMS - Page Builder'],
        parameters: [
            new OA\Parameter(
                name: 'sectionId',
                description: 'Section ID to duplicate',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Section duplicated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/SectionResource'),
                        new OA\Property(property: 'message', type: 'string')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Section not found',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundError')
            )
        ]
    )]
    public function duplicateSection(int $sectionId): JsonResponse
    {
        $section = Section::findOrFail($sectionId);
        $this->authorize('update', $section->page);

        $duplicatedSection = $this->pageBuilderService->duplicateSection($sectionId);

        return $this->success($duplicatedSection, 'Section duplicated successfully', 201);
    }

    /**
     * Preview page.
     */
    #[OA\Get(
        path: '/api/v1/cms/page-builder/pages/{pageId}/preview',
        summary: 'Preview page',
        description: 'Get page preview data as it would appear to visitors',
        tags: ['CMS - Page Builder'],
        parameters: [
            new OA\Parameter(
                name: 'pageId',
                description: 'Page ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Page preview retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'page',
                                    properties: [
                                        new OA\Property(property: 'title', type: 'string'),
                                        new OA\Property(property: 'meta_title', type: 'string'),
                                        new OA\Property(property: 'meta_description', type: 'string')
                                    ]
                                ),
                                new OA\Property(
                                    property: 'sections',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'type', type: 'string'),
                                            new OA\Property(property: 'content', type: 'object')
                                        ]
                                    )
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Page not found',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundError')
            )
        ]
    )]
    public function previewPage(int $pageId): JsonResponse
    {
        $page = Page::findOrFail($pageId);
        $this->authorize('view', $page);

        $previewData = $this->pageBuilderService->previewPage($pageId);

        return $this->success($previewData, 'Page preview retrieved successfully');
    }
}