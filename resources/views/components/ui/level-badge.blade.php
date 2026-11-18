@props(['level'])

@php
    $level = $level instanceof \App\Enums\ResultLevel ? $level : \App\Enums\ResultLevel::tryFrom((string) $level);
    $tone = match ($level) {
        \App\Enums\ResultLevel::Strength => 'green',
        \App\Enums\ResultLevel::Developing => 'amber',
        \App\Enums\ResultLevel::ToDevelop => 'red',
        default => 'slate',
    };
@endphp

<span {{ $attributes->class(['ws-badge', 'ws-badge--'.$tone]) }}>{{ $level?->label() ?? 'Sin nivel' }}</span>
