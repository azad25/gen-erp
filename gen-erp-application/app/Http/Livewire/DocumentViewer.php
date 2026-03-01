<?php

namespace App\Http\Livewire;

use App\Models\Document;
use App\Services\DocumentService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class DocumentViewer extends Component
{
    public int $documentId;
    public ?Document $document = null;
    public ?string $thumbnailUrl = null;
    public ?string $previewUrl = null;
    public bool $isLoading = true;
    public string $error = '';

    public function mount(int $documentId): void
    {
        $this->documentId = $documentId;
        $this->loadDocument();
    }

    public function loadDocument(): void
    {
        try {
            $this->document = Document::withoutGlobalScopes()->find($this->documentId);
            
            if (! $this->document) {
                $this->error = 'Document not found';
                $this->isLoading = false;
                return;
            }

            $service = app(DocumentService::class);
            $this->thumbnailUrl = $service->getThumbnailUrl($this->document);
            $this->previewUrl = $service->getPreviewUrl($this->document);
            $this->isLoading = false;
        } catch (\Throwable $e) {
            $this->error = 'Failed to load document: ' . $e->getMessage();
            $this->isLoading = false;
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.document-viewer');
    }
}
