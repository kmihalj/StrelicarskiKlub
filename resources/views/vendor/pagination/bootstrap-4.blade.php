@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $showControls = $last > 11;
        $slots = [];

        if (!$showControls) {
            $slots = range(1, $last);
        } elseif ($current <= 4) {
            $slots = [1, 2, 3, 4, 5, 'dots-right', $last];
        } elseif ($current >= ($last - 3)) {
            $slots = [1, 'dots-left', $last - 4, $last - 3, $last - 2, $last - 1, $last];
        } else {
            $slots = [1, 'dots-left', $current - 1, $current, $current + 1, 'dots-right', $last];
        }

        $slots = array_values(array_filter($slots, function ($value) use ($last) {
            if (is_string($value)) {
                return true;
            }

            return $value >= 1 && $value <= $last;
        }));

        if ($showControls) {
            while (count($slots) < 7) {
                $slots[] = 'spacer';
            }
        }
    @endphp

    <nav aria-label="Navigacija stranica">
        <ul class="pagination pagination-compact">
            @if ($showControls)
                @if ($paginator->onFirstPage())
                    <li class="page-item page-control page-control-first disabled" aria-disabled="true" aria-label="Prva stranica">
                        <span class="page-link" aria-hidden="true">&laquo;</span>
                    </li>
                    <li class="page-item page-control page-control-prev disabled" aria-disabled="true" aria-label="Prethodna stranica">
                        <span class="page-link" aria-hidden="true">&lsaquo;</span>
                    </li>
                @else
                    <li class="page-item page-control page-control-first">
                        <a class="page-link" href="{{ $paginator->url(1) }}" aria-label="Prva stranica">&laquo;</a>
                    </li>
                    <li class="page-item page-control page-control-prev">
                        <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Prethodna stranica">&lsaquo;</a>
                    </li>
                @endif
            @endif

            @foreach ($slots as $slot)
                @if ($slot === 'dots-left' || $slot === 'dots-right')
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">&hellip;</span>
                    </li>
                @elseif ($slot === 'spacer')
                    <li class="page-item disabled page-spacer" aria-hidden="true">
                        <span class="page-link">&nbsp;</span>
                    </li>
                @elseif ($slot == $current)
                    <li class="page-item active" aria-current="page">
                        <span class="page-link">{{ $slot }}</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->url($slot) }}">{{ $slot }}</a>
                    </li>
                @endif
            @endforeach

            @if ($showControls)
                @if ($paginator->hasMorePages())
                    <li class="page-item page-control page-control-next">
                        <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Sljedeća stranica">&rsaquo;</a>
                    </li>
                    <li class="page-item page-control page-control-last">
                        <a class="page-link" href="{{ $paginator->url($last) }}" aria-label="Zadnja stranica">&raquo;</a>
                    </li>
                @else
                    <li class="page-item page-control page-control-next disabled" aria-disabled="true" aria-label="Sljedeća stranica">
                        <span class="page-link" aria-hidden="true">&rsaquo;</span>
                    </li>
                    <li class="page-item page-control page-control-last disabled" aria-disabled="true" aria-label="Zadnja stranica">
                        <span class="page-link" aria-hidden="true">&raquo;</span>
                    </li>
                @endif
            @endif
        </ul>
    </nav>
@endif
