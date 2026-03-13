@if ($paginator->hasPages())
<div class="flex items-center gap-2">

    {{-- Previous --}}
    @if ($paginator->onFirstPage())
        <span class="px-2 py-1 bg-gray-300 rounded opacity-50">
            <i class="fas fa-chevron-left"></i>
        </span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}"
           class="px-2 py-1 bg-gray-400 hover:bg-gray-500 text-white rounded">
            <i class="fas fa-chevron-left"></i>
        </a>
    @endif


    {{-- Page Numbers --}}
    @foreach ($elements as $element)

        {{-- jika berupa string "..." --}}
        @if (is_string($element))
            <span class="px-3 py-1">{{ $element }}</span>
        @endif

        {{-- jika berupa array halaman --}}
        @if (is_array($element))
            @foreach ($element as $page => $url)

                @if ($page == $paginator->currentPage())
                    <span class="px-3 py-1 bg-sky-600 text-white rounded">
                        {{ $page }}
                    </span>
                @else
                    <a href="{{ $url }}"
                       class="px-3 py-1 bg-gray-200 hover:bg-gray-300 rounded">
                        {{ $page }}
                    </a>
                @endif

            @endforeach
        @endif

    @endforeach


    {{-- Next --}}
    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}"
           class="px-2 py-1 bg-gray-400 hover:bg-gray-500 text-white rounded">
            <i class="fas fa-chevron-right"></i>
        </a>
    @else
        <span class="px-2 py-1 bg-gray-300 rounded opacity-50">
            <i class="fas fa-chevron-right"></i>
        </span>
    @endif

</div>
@endif