@props(['messages' => [], 'field' => null])
@if ($field)
    <p x-cloak x-show="form.invalid(@js($field))" x-text="form.errors[@js($field)]" {{ $attributes->class(['mt-1.5 text-xs font-medium leading-5 text-red-600']) }}></p>
@endif
@if ($messages)
    <ul {{ $attributes->class(['mt-1.5 space-y-1 text-xs font-medium leading-5 text-red-600']) }}>
        @foreach ((array) $messages as $message)<li>{{ $message }}</li>@endforeach
    </ul>
@endif
