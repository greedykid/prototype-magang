@props([
    'title',
    'subtitle' => null,
    'backUrl' => null,
    'backText' => 'Kembali',
])

<div class="page-heading">
    <div>
        @if($backUrl)
            <a class="back-link" href="{{ $backUrl }}">{{ $backText }}</a>
        @endif
        <h1>{{ $title }}</h1>
        @if($subtitle)
            <p class="lede">{{ $subtitle }}</p>
        @endif
    </div>
    @if(isset($actions) || (isset($slot) && $slot->isNotEmpty()))
        <div class="page-heading-actions" style="display: inline-flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            {{ $actions ?? $slot }}
        </div>
    @endif
</div>
