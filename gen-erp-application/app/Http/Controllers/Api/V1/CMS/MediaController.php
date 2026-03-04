<?php

namespace App\Http\Controllers\Api\V1\CMS;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

/**
 * @OA\Tag(
 *     name="CMS - Media",
 *     description="CMS media upload and management"
 * )
 */
class MediaController extends BaseApiController
{
    /**
     * @OA\Post(
     *     path="/api/v1/cms/media/upload",
     *     summary="Upload media file",
     *     tags={"CMS - Media"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="Media file to upload"
     *                 ),
     *                 @OA\Property(
     *                     property="alt",
     *                     type="string",
     *                     description="Alt text for the image"
     *                 ),
     *                 @OA\Property(
     *                     property="resize_width",
     *                     type="integer",
     *                     description="Resize image to this width (optional)"
     *                 ),
     *                 @OA\Property(
     *                     property="resize_height",
     *                     type="integer",
     *                     description="Resize image to this height (optional)"
     *                 ),
     *                 @OA\Property(
     *                     property="quality",
     *                     type="integer",
     *                     description="Image quality (1-100, default: 85)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="File uploaded successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="url", type="string", example="https://example.com/storage/cms/images/image.jpg"),
     *                 @OA\Property(property="path", type="string", example="cms/images/image.jpg"),
     *                 @OA\Property(property="filename", type="string", example="image.jpg"),
     *                 @OA\Property(property="size", type="integer", example=1024000),
     *                 @OA\Property(property="mime_type", type="string", example="image/jpeg"),
     *                 @OA\Property(property="alt", type="string", example="Alt text"),
     *                 @OA\Property(property="width", type="integer", example=1920),
     *                 @OA\Property(property="height", type="integer", example=1080)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="file",
     *                     type="array",
     *                     @OA\Items(type="string", example="The file field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:jpeg,jpg,png,gif,webp,svg|max:10240', // 10MB max
            'alt' => 'nullable|string|max:255',
            'resize_width' => 'nullable|integer|min:1|max:4000',
            'resize_height' => 'nullable|integer|min:1|max:4000',
            'quality' => 'nullable|integer|min:1|max:100'
        ]);

        $file = $request->file('file');
        $companyId = $request->user()->currentCompany->id;
        
        // Generate unique filename
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = "cms/company-{$companyId}/images/" . date('Y/m');
        
        try {
            // Process image if it's an image file
            if (in_array($file->getMimeType(), ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'])) {
                $image = Image::make($file);
                
                // Get original dimensions
                $originalWidth = $image->width();
                $originalHeight = $image->height();
                
                // Resize if requested
                if ($validated['resize_width'] || $validated['resize_height']) {
                    $width = $validated['resize_width'] ?? null;
                    $height = $validated['resize_height'] ?? null;
                    
                    $image->resize($width, $height, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }
                
                // Set quality
                $quality = $validated['quality'] ?? 85;
                
                // Save processed image
                $fullPath = $path . '/' . $filename;
                Storage::disk('public')->put($fullPath, $image->encode(null, $quality));
                
                $fileSize = Storage::disk('public')->size($fullPath);
                $dimensions = [
                    'width' => $image->width(),
                    'height' => $image->height()
                ];
            } else {
                // For non-image files, just store directly
                $fullPath = $file->storeAs($path, $filename, 'public');
                $fileSize = $file->getSize();
                $dimensions = [];
            }
            
            $url = Storage::disk('public')->url($fullPath);
            
            return $this->success([
                'url' => $url,
                'path' => $fullPath,
                'filename' => $file->getClientOriginalName(),
                'size' => $fileSize,
                'mime_type' => $file->getMimeType(),
                'alt' => $validated['alt'] ?? '',
                ...$dimensions
            ], 'File uploaded successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to upload file: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/cms/media/{path}",
     *     summary="Delete media file",
     *     tags={"CMS - Media"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="path",
     *         in="path",
     *         required=true,
     *         description="File path to delete",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="File deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="File not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="File not found")
     *         )
     *     )
     * )
     */
    public function delete(Request $request, string $path): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        
        // Ensure the file belongs to the current company
        if (!str_contains($path, "company-{$companyId}")) {
            return $this->error('Unauthorized', 403);
        }
        
        if (!Storage::disk('public')->exists($path)) {
            return $this->error('File not found', 404);
        }
        
        try {
            Storage::disk('public')->delete($path);
            return $this->success(null, 'File deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete file: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/cms/media",
     *     summary="List media files",
     *     tags={"CMS - Media"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", default=20, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="File type filter",
     *         @OA\Schema(type="string", enum={"image", "video", "document"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Media files retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Media files retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="files",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="path", type="string"),
     *                         @OA\Property(property="url", type="string"),
     *                         @OA\Property(property="filename", type="string"),
     *                         @OA\Property(property="size", type="integer"),
     *                         @OA\Property(property="mime_type", type="string"),
     *                         @OA\Property(property="last_modified", type="string")
     *                     )
     *                 ),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="last_page", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
            'type' => 'string|in:image,video,document'
        ]);

        $companyId = $request->user()->currentCompany->id;
        $perPage = $validated['per_page'] ?? 20;
        $page = $validated['page'] ?? 1;
        $type = $validated['type'] ?? null;
        
        $directory = "cms/company-{$companyId}";
        
        try {
            $allFiles = collect(Storage::disk('public')->allFiles($directory))
                ->map(function ($path) {
                    $url = Storage::disk('public')->url($path);
                    $size = Storage::disk('public')->size($path);
                    $lastModified = Storage::disk('public')->lastModified($path);
                    $mimeType = Storage::disk('public')->mimeType($path);
                    
                    return [
                        'path' => $path,
                        'url' => $url,
                        'filename' => basename($path),
                        'size' => $size,
                        'mime_type' => $mimeType,
                        'last_modified' => date('Y-m-d H:i:s', $lastModified),
                        'type' => $this->getFileType($mimeType)
                    ];
                });
            
            // Filter by type if specified
            if ($type) {
                $allFiles = $allFiles->filter(function ($file) use ($type) {
                    return $file['type'] === $type;
                });
            }
            
            // Sort by last modified (newest first)
            $allFiles = $allFiles->sortByDesc('last_modified');
            
            // Paginate
            $total = $allFiles->count();
            $files = $allFiles->forPage($page, $perPage)->values();
            $lastPage = ceil($total / $perPage);
            
            return $this->success([
                'files' => $files,
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $lastPage
            ], 'Media files retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve media files: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get file type based on MIME type
     */
    private function getFileType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }
        
        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }
        
        return 'document';
    }
}