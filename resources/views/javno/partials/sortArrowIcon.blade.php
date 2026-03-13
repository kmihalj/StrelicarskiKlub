@php
    $sortState = $state ?? 'both';
@endphp
<span class="js-sort-arrow clanovi-sort-icon" data-sort-state="{{ $sortState }}" aria-hidden="true">
    <svg class="sort-icon-svg sort-icon-both" viewBox="0 0 16 16" focusable="false">
        <path d="M8 2 5.25 4.75h1.9v6.5h1.7v-6.5h1.9L8 2Z"></path>
        <path d="M8 14 10.75 11.25h-1.9v-6.5h-1.7v6.5h-1.9L8 14Z"></path>
    </svg>
    <svg class="sort-icon-svg sort-icon-asc" viewBox="0 0 16 16" focusable="false">
        <path d="M8 2 5.25 4.75h1.9V14h1.7V4.75h1.9L8 2Z"></path>
    </svg>
    <svg class="sort-icon-svg sort-icon-desc" viewBox="0 0 16 16" focusable="false">
        <path d="M8 14 10.75 11.25h-1.9V2h-1.7v9.25h-1.9L8 14Z"></path>
    </svg>
</span>
