@props(['value', 'label' => 'Progreso'])

<progress {{ $attributes->class('ws-progress') }} value="{{ max(0, min(100, (float) $value)) }}" max="100" aria-label="{{ $label }}"></progress>
