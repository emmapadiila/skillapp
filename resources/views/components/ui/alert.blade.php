@props(['tone' => 'info'])

<div role="{{ $tone === 'danger' ? 'alert' : 'status' }}" {{ $attributes->class(['ws-alert', 'ws-alert--'.$tone]) }}>{{ $slot }}</div>
