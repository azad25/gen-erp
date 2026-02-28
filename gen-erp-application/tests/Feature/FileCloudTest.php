<?php

use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\Product;
use App\Models\User;
use App\Services\CompanyContext;
use App\Services\DocumentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    CompanyContext::setActive($this->company);
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company->id, ['role' => 'owner']);
});

// ── DocumentService: Upload & Retrieve ──────────────────────────

test('DocumentService uploads a file and creates Document record', function (): void {
    $service = app(DocumentService::class);

    $file = UploadedFile::fake()->image('product-photo.jpg', 800, 600);

    $doc = $service->upload(
        $file,
        $this->company->id,
        $this->user->id,
        description: 'Main product image',
    );

    expect($doc)->toBeInstanceOf(Document::class);
    expect($doc->name)->toBe('product-photo.jpg');
    expect($doc->mime_type)->toBe('image/jpeg');
    expect($doc->size_bytes)->toBeGreaterThan(0);
    expect($doc->description)->toBe('Main product image');
    expect($doc->company_id)->toBe($this->company->id);
    expect($doc->uploaded_by)->toBe($this->user->id);
    // Verify disk_path is well-formed
    expect($doc->disk_path)->toContain("{$this->company->id}/");
    expect($doc->disk_path)->toMatch('/\.jpg$/');
});

test('DocumentService uploadRaw stores raw content', function (): void {
    $service = app(DocumentService::class);

    $doc = $service->uploadRaw(
        'Hello World PDF Content',
        'invoice-001.pdf',
        'application/pdf',
        $this->company->id,
        $this->user->id,
    );

    expect($doc->name)->toBe('invoice-001.pdf');
    expect($doc->mime_type)->toBe('application/pdf');
    expect($doc->size_bytes)->toBe(23); // strlen of content

    $contents = $service->getContents($doc);
    expect($contents)->toBe('Hello World PDF Content');

    // Cleanup
    @unlink(storage_path("app/private/{$doc->disk_path}"));
});

test('DocumentService rejects disallowed MIME types', function (): void {
    $service = app(DocumentService::class);

    $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');

    $service->upload($file, $this->company->id, $this->user->id);
})->throws(ValidationException::class);

// ── Storage Quota Enforcement ───────────────────────────────────

test('storage quota blocks upload when exceeded', function (): void {
    $service = app(DocumentService::class);

    // Create a document that consumes all storage (free tier = 50MB)
    Document::withoutGlobalScopes()->create([
        'company_id' => $this->company->id,
        'name' => 'huge-file.bin',
        'disk_path' => 'fake/path.bin',
        'mime_type' => 'application/octet-stream',
        'size_bytes' => DocumentService::STORAGE_QUOTAS['free'], // exact limit
        'uploaded_by' => $this->user->id,
        'uploaded_at' => now(),
    ]);

    $file = UploadedFile::fake()->image('one-more.jpg', 10, 10);

    $service->upload($file, $this->company->id, $this->user->id);
})->throws(ValidationException::class, 'Storage quota exceeded');

test('storage usage percent calculates correctly', function (): void {
    $service = app(DocumentService::class);

    // Free tier = 50MB. Create 25MB used.
    Document::withoutGlobalScopes()->create([
        'company_id' => $this->company->id,
        'name' => 'half-used.bin',
        'disk_path' => 'fake/half.bin',
        'mime_type' => 'application/octet-stream',
        'size_bytes' => 26214400, // 25MB
        'uploaded_by' => $this->user->id,
        'uploaded_at' => now(),
    ]);

    $percent = $service->storageUsagePercent($this->company->id);
    expect($percent)->toBe(50.0);

    $remaining = $service->storageRemaining($this->company->id);
    expect($remaining)->toBe('25 MB');
});

// ── DocumentFolder Hierarchy ────────────────────────────────────

test('folders support nested hierarchy with breadcrumbs', function (): void {
    $service = app(DocumentService::class);

    $root = $service->createFolder($this->company->id, 'Documents', null, $this->user->id);
    $invoices = $service->createFolder($this->company->id, 'Invoices', $root->id, $this->user->id);
    $january = $service->createFolder($this->company->id, 'January', $invoices->id, $this->user->id);

    expect($root->path)->toBe('/Documents');
    expect($invoices->path)->toBe('/Documents/Invoices');
    expect($january->path)->toBe('/Documents/Invoices/January');

    $crumbs = $january->breadcrumbs();
    expect($crumbs)->toHaveCount(3);
    expect($crumbs[0]['name'])->toBe('Documents');
    expect($crumbs[2]['name'])->toBe('January');
});

// ── HasDocuments Trait ───────────────────────────────────────────

test('HasDocuments trait attaches files to a product', function (): void {
    $service = app(DocumentService::class);

    $product = Product::withoutGlobalScopes()->create([
        'company_id' => $this->company->id,
        'name' => 'Widget A',
        'slug' => 'widget-a',
        'sku' => 'WDG-001',
        'selling_price' => 150000,
        'cost_price' => 100000,
    ]);

    $doc = $service->uploadRaw(
        'product image bytes',
        'widget-a.jpg',
        'image/jpeg',
        $this->company->id,
        $this->user->id,
        entityType: Product::class,
        entityId: $product->id,
    );

    expect($product->documents()->count())->toBe(1);
    expect($product->latestDocument()->name)->toBe('widget-a.jpg');
    expect($product->images()->count())->toBe(1);

    // Cleanup
    @unlink(storage_path("app/private/{$doc->disk_path}"));
});

// ── Document Model Helpers ──────────────────────────────────────

test('Document model provides MIME icon and formatted size', function (): void {
    $doc = new Document([
        'name' => 'report.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 2621440, // 2.5MB
    ]);

    expect($doc->mimeIcon())->toBe('heroicon-o-document-text');
    expect($doc->formattedSize())->toBe('2.5 MB');
    expect($doc->isPreviewable())->toBeTrue();
    expect($doc->isImage())->toBeFalse();
    expect($doc->extension())->toBe('pdf');
});

test('Document model generates signed download URL', function (): void {
    $doc = Document::withoutGlobalScopes()->create([
        'company_id' => $this->company->id,
        'name' => 'test.pdf',
        'disk_path' => 'fake/test.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 1024,
        'uploaded_by' => $this->user->id,
        'uploaded_at' => now(),
    ]);

    $url = $doc->signedUrl();
    expect($url)->toContain('/documents/');
    expect($url)->toContain('signature=');
});

// ── Tenant Isolation ────────────────────────────────────────────

test('documents are isolated between companies', function (): void {
    $companyB = Company::factory()->create();

    Document::withoutGlobalScopes()->create([
        'company_id' => $this->company->id,
        'name' => 'company-a.pdf',
        'disk_path' => 'a/file.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 1024,
        'uploaded_by' => $this->user->id,
        'uploaded_at' => now(),
    ]);

    Document::withoutGlobalScopes()->create([
        'company_id' => $companyB->id,
        'name' => 'company-b.pdf',
        'disk_path' => 'b/file.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 1024,
        'uploaded_by' => $this->user->id,
        'uploaded_at' => now(),
    ]);

    // Company A context
    CompanyContext::setActive($this->company);
    expect(Document::count())->toBe(1);

    // Company B context
    CompanyContext::setActive($companyB);
    expect(Document::count())->toBe(1);
});

// ── Soft Delete ─────────────────────────────────────────────────

test('deleted documents can be restored', function (): void {
    $doc = Document::withoutGlobalScopes()->create([
        'company_id' => $this->company->id,
        'name' => 'recoverable.pdf',
        'disk_path' => 'r/file.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 512,
        'uploaded_by' => $this->user->id,
        'uploaded_at' => now(),
    ]);

    $doc->delete();
    expect(Document::withoutGlobalScopes()->where('deleted_at', null)->count())->toBe(0);
    expect(Document::withTrashed()->withoutGlobalScopes()->count())->toBe(1);

    $doc->restore();
    expect(Document::withoutGlobalScopes()->where('deleted_at', null)->count())->toBe(1);
});
