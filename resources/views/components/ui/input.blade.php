@props(['name', 'label', 'type' => 'text', 'trailing' => null])

@php($id = $attributes->get('id', $name))

<div class="ws-field">
    <label for="{{ $id }}">{{ $label }}</label>
    <div class="relative">
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror {{ $attributes->except('id')->class(['auth-input', 'pr-12' => $trailing !== null]) }}>
        {{ $trailing }}
    </div>
    @error($name)<p id="{{ $id }}-error" class="text-xs font-normal text-danger-700" role="alert">{{ $message }}</p>@enderror
</div>
