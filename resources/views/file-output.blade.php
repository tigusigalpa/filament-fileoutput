<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div class="filament-fileoutput-wrapper">
        @if($hasFile())
            <div class="flex items-center gap-4 p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600">
                @if($isImage())
                    <div class="flex-shrink-0">
                        <img 
                            src="{{ $getFileUrl() }}" 
                            alt="File preview" 
                            class="max-w-xs max-h-48 rounded-lg shadow-sm object-contain"
                            style="max-width: 300px; max-height: 200px;"
                        />
                    </div>
                @else
                    <div class="flex-1">
                        <a 
                            href="{{ $getFileUrl() }}" 
                            target="_blank"
                            class="text-primary-600 dark:text-primary-400 hover:underline font-medium"
                        >
                            <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            {{ __('Download File') }}
                        </a>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ basename($getFilePath()) }}
                        </div>
                    </div>
                @endif

                @if($getDeleteAction())
                    <div class="flex-shrink-0">
                        {{ ($getDeleteAction()) }}
                    </div>
                @endif
            </div>
        @else
            <div class="text-sm text-gray-500 dark:text-gray-400 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                {{ __('No file uploaded') }}
            </div>
        @endif
    </div>
</x-dynamic-component>
