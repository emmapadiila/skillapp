@props(['icon', 'title', 'description', 'tone' => 'blue'])

@php
    $toneClasses = match ($tone) {
        'cyan' => 'text-cyan-300 border-cyan-400/20 bg-cyan-400/10',
        'emerald' => 'text-emerald-300 border-emerald-400/20 bg-emerald-400/10',
        'violet' => 'text-violet-300 border-violet-400/20 bg-violet-400/10',
        default => 'text-blue-300 border-blue-400/20 bg-blue-400/10',
    };
@endphp

<div {{ $attributes->class(['group flex items-start gap-4']) }}>
    <div class="grid size-12 shrink-0 place-items-center rounded-xl border transition-transform duration-300 group-hover:-translate-y-0.5 {{ $toneClasses }}">
        @if ($icon === 'brain')
            <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                <path d="M9.5 5.2A3 3 0 0 0 4.8 7a3 3 0 0 0-1 5.2A3.5 3.5 0 0 0 7 17.8 3 3 0 0 0 12 20V4a3 3 0 0 0-2.5 1.2Z"/><path d="M7 9.5c1.2 0 2.2.7 2.6 1.7M7 17.8c-.2-1.7.5-3 2-3.8M14.5 5.2A3 3 0 0 1 19.2 7a3 3 0 0 1 1 5.2 3.5 3.5 0 0 1-3.2 5.6A3 3 0 0 1 12 20V4a3 3 0 0 1 2.5 1.2Z"/><path d="M17 9.5c-1.2 0-2.2.7-2.6 1.7M17 17.8c.2-1.7-.5-3-2-3.8"/>
            </svg>
        @elseif ($icon === 'chart')
            <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                <path d="M4 20V10h4v10M10 20V4h4v16M16 20v-7h4v7M3 20h18M4 7l5-3 4 3 7-5"/>
            </svg>
        @elseif ($icon === 'target')
            <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                <circle cx="11" cy="13" r="8"/><circle cx="11" cy="13" r="4"/><path d="m11 13 9-9M16 4h4v4"/>
            </svg>
        @else
            <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                <path d="M12 3 5 6v5c0 4.6 2.8 8.1 7 10 4.2-1.9 7-5.4 7-10V6l-7-3Z"/><path d="M9.5 12V10a2.5 2.5 0 0 1 5 0v2M9 12h6v4H9z"/>
            </svg>
        @endif
    </div>
    <div class="pt-0.5">
        <h3 class="text-sm font-semibold text-white">{{ $title }}</h3>
        <p class="mt-1 max-w-xs text-xs leading-relaxed text-blue-100/65">{{ $description }}</p>
    </div>
</div>
