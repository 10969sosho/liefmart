@if ($paginator->hasPages())
    <nav class="d-flex justify-items-center justify-content-end">
        <div class="d-flex justify-content-end flex-fill d-sm-none">
            <ul class="pagination pagination-sm">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link border-0 rounded-start-pill">&lsaquo;</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link border-0 rounded-start-pill" href="{{ $paginator->previousPageUrl() }}" rel="prev">&lsaquo;</a>
                    </li>
                @endif

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <a class="page-link border-0 rounded-end-pill" href="{{ $paginator->nextPageUrl() }}" rel="next">&rsaquo;</a>
                    </li>
                @else
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link border-0 rounded-end-pill">&rsaquo;</span>
                    </li>
                @endif
            </ul>
        </div>

        <div class="d-none flex-sm-fill d-sm-flex align-items-sm-center justify-content-sm-between">
            <div>
                <p class="small text-muted mb-0">
                    {{ __('Menampilkan') }}
                    <span class="fw-semibold">{{ $paginator->firstItem() }}</span>
                    {{ __('sampai') }}
                    <span class="fw-semibold">{{ $paginator->lastItem() }}</span>
                    {{ __('dari') }}
                    <span class="fw-semibold">{{ $paginator->total() }}</span>
                    {{ __('hasil') }}
                </p>
            </div>

            <div>
                <ul class="pagination pagination-sm mb-0">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                            <span class="page-link border-0" aria-hidden="true">&lsaquo;</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link border-0" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo;</a>
                        </li>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <li class="page-item disabled" aria-disabled="true"><span class="page-link border-0">{{ $element }}</span></li>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @php
                                $currentPage = $paginator->currentPage();
                                $lastPage = $paginator->lastPage();
                            @endphp
                            
                            @foreach ($element as $page => $url)
                                {{-- Show only first 3 pages, current page, and last page --}}
                                @if ($page <= 3 || $page == $currentPage || $page == $lastPage)
                                    @if ($page == $currentPage)
                                        <li class="page-item active" aria-current="page"><span class="page-link border-0 fw-bold">{{ $page }}</span></li>
                                    @else
                                        <li class="page-item"><a class="page-link border-0" href="{{ $url }}">{{ $page }}</a></li>
                                    @endif
                                @elseif ($page == $lastPage - 1 && $currentPage < $lastPage - 3)
                                    <li class="page-item disabled" aria-disabled="true"><span class="page-link border-0">...</span></li>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <li class="page-item">
                            <a class="page-link border-0" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a>
                        </li>
                    @else
                        <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                            <span class="page-link border-0" aria-hidden="true">&rsaquo;</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>
@endif 