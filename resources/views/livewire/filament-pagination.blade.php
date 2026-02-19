@if ($paginator->hasPages())
    <div class="filament-tables-pagination-container flex items-center justify-between">
        <div class="flex items-center">
            <div class="filament-tables-pagination-records-per-page-selector">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $paginator->total() }} {{ __('records') }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <div class="flex items-center gap-1">
                @if (!$paginator->onFirstPage())
                    <a
                       wire:click="previousPage('{{ $paginator->getPageName() }}')"
                       wire:key="pagination-{{ $paginator->getPageName() }}-page-{{ $paginator->currentPage() }}-first"
                       class="filament-tables-pagination-link cursor-pointer px-2 py-1 text-sm font-medium text-gray-700 hover:text-primary-500 dark:text-gray-200 dark:hover:text-primary-400"
                       aria-label="{{ __('pagination.previous') }}">
                        <svg class="h-5 w-5 rtl:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                @else
                    <span class="filament-tables-pagination-link px-2 py-1 text-sm font-medium text-gray-400 dark:text-gray-500">
                        <svg class="h-5 w-5 rtl:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="filament-tables-pagination-link px-2 py-1 text-sm font-medium text-gray-400 dark:text-gray-500">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            <a
                               wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                               wire:key="pagination-{{ $paginator->getPageName() }}-page-{{ $page }}"
                               class="filament-tables-pagination-link {{ $page === $paginator->currentPage() ? 'cursor-default bg-primary-600 text-white dark:bg-primary-500' : 'cursor-pointer text-gray-700 hover:text-primary-500 dark:text-gray-200 dark:hover:text-primary-400' }} flex h-8 w-8 items-center justify-center rounded-md text-sm font-medium"
                               aria-label="{{ __('pagination.goto_page', ['page' => $page]) }}"
                               aria-current="{{ $page === $paginator->currentPage() ? 'page' : 'false' }}">
                                {{ $page }}
                            </a>
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a
                       wire:click="nextPage('{{ $paginator->getPageName() }}')"
                       wire:key="pagination-{{ $paginator->getPageName() }}-page-{{ $paginator->currentPage() }}-next"
                       class="filament-tables-pagination-link cursor-pointer px-2 py-1 text-sm font-medium text-gray-700 hover:text-primary-500 dark:text-gray-200 dark:hover:text-primary-400"
                       aria-label="{{ __('pagination.next') }}">
                        <svg class="h-5 w-5 rtl:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                @else
                    <span class="filament-tables-pagination-link px-2 py-1 text-sm font-medium text-gray-400 dark:text-gray-500">
                        <svg class="h-5 w-5 rtl:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                @endif
            </div>
        </div>
    </div>
@endif

