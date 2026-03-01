<div>
    @if ($isLoading)
        <div class="flex items-center justify-center h-screen">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900"></div>
        </div>
    @elseif ($error)
        <div class="flex items-center justify-center h-screen">
            <div class="text-red-600">{{ $error }}</div>
        </div>
    @else
        <div class="flex flex-col h-screen bg-gray-50">
            <div class="flex-1 flex flex-col overflow-hidden">
                <!-- Toolbar -->
                <div class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <h2 class="text-lg font-semibold">{{ $document->name }}</h2>
                        <span class="text-sm text-gray-500">{{ $document->formattedSize() }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($previewUrl)
                            <a href="{{ $previewUrl }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                                <x-heroicon-o-eye class="w-5 h-5" />
                                <span>Open Preview</span>
                            </a>
                        @endif
                        <a href="{{ $document->signedUrl() }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition">
                            <x-heroicon-o-arrow-down-tray class="w-5 h-5" />
                            <span>Download</span>
                        </a>
                    </div>
                </div>

                <!-- Viewer Area -->
                <div class="flex-1 overflow-auto bg-gray-900 flex items-center justify-center p-8">
                    @if ($document->isImage())
                        @if ($thumbnailUrl)
                            <img 
                                src="{{ $thumbnailUrl }}" 
                                alt="{{ $document->name }}" 
                                class="max-w-full max-h-full object-contain"
                            />
                        @else
                            <div class="text-white">Unable to generate thumbnail</div>
                        @endif
                    @elseif ($document->mime_type === 'application/pdf')
                        <div class="w-full h-full flex items-center justify-center">
                            <div class="text-white text-center">
                                <x-heroicon-o-document-text class="w-16 h-16 mx-auto mb-4 text-gray-400" />
                                <p class="text-gray-400">PDF Preview</p>
                                <p class="text-sm text-gray-500 mt-2">
                                    <a href="{{ $document->signedUrl() }}" class="text-blue-400 hover:text-blue-300">
                                        Download to view
                                    </a>
                                </p>
                            </div>
                        </div>
                    @else
                        <div class="text-white text-center">
                            <x-heroicon-o-document class="w-16 h-16 mx-auto mb-4 text-gray-400" />
                            <p class="text-gray-400">Preview not available for this file type</p>
                            <p class="text-sm text-gray-500 mt-2">
                                <a href="{{ $document->signedUrl() }}" class="text-blue-400 hover:text-blue-300">
                                    Download to view
                                </a>
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
