@props([
    'tag' => 'td',
])

<{{ $tag }}
    {{ $attributes->class(['fi-ta-cell px-4 py-3 sm:first-of-type:ps-5 sm:last-of-type:pe-5']) }}
>
    {{ $slot }}
</{{ $tag }}>
