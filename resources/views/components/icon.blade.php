@props(['name', 'size' => 18])

@php
    $paths = [
        'arrow-left' => '<path d="m15 18-6-6 6-6M9 12h10"/>',
        'arrow-right' => '<path d="m9 18 6-6-6-6M5 12h10"/>',
        'calendar' => '<rect width="18" height="16" x="3" y="5" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        'calendar-plus' => '<rect width="18" height="16" x="3" y="5" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M12 13v4M10 15h4"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/>',
        'eye' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/>',
        'file-plus' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M12 18v-6M9 15h6"/>',
        'log-out' => '<path d="M10 17l5-5-5-5M15 12H3"/><path d="M14 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'refresh' => '<path d="M20 11a8 8 0 0 0-14.9-3L3 11"/><path d="M3 4v7h7M4 13a8 8 0 0 0 14.9 3L21 13"/><path d="M21 20v-7h-7"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'x' => '<path d="m6 6 12 12M18 6 6 18"/>',
    ];
@endphp

<svg {{ $attributes->merge(['width' => $size, 'height' => $size, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true', 'focusable' => 'false']) }}>
    {!! $paths[$name] ?? '' !!}
</svg>
