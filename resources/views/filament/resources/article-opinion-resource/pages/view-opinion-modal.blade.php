<div class="space-y-6">
    <!-- Información de la Ley y Artículo -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Ley o Reglamento</label>
            <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">
                {{ $record->article->law->name ?? 'No disponible' }}
            </p>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Artículo</label>
            <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">
                {{ $record->article->article_title ?? 'No disponible' }}
            </p>
        </div>
    </div>

    <!-- Opinión -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Opinión</label>
        <div class="bg-gray-50 px-3 py-2 rounded-md prose prose-sm max-w-none">
            {!! $record->opinion !!}
        </div>
    </div>

    <!-- Archivo -->
    @if($record->url_file)
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Archivo Adjunto</label>
        <div class="bg-gray-50 px-3 py-2 rounded-md">
            <a href="{{ $record->url_file_full }}" 
               target="_blank" 
               class="text-blue-600 hover:text-blue-800 underline flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Ver archivo
            </a>
        </div>
    </div>
    @endif

    <!-- Fechas -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Creación</label>
            <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">
                {{ $record->created_at->format('d/m/Y H:i') }}
            </p>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Última Actualización</label>
            <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">
                {{ $record->updated_at->format('d/m/Y H:i') }}
            </p>
        </div>
    </div>
</div> 