@props(['variant' => 'primary', 'type' => 'button'])

<button type="{{ $type }}" {{ $attributes->class([$variant === 'primary' ? 'ws-primary' : 'ws-button']) }}>{{ $slot }}</button>
