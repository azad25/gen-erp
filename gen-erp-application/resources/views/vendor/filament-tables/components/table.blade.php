@props([
    'footer' => null,
    'header' => null,
    'headerGroups' => null,
    'reorderable' => false,
    'reorderAnimationDuration' => 300,
])

<table
    {{ $attributes->class(['fi-ta-table w-full table-auto text-start']) }}
>
    @if ($header)
        <thead>
            @if ($headerGroups)
                <tr class="bg-transparent">
                    {{ $headerGroups }}
                </tr>
            @endif

            <tr class="bg-transparent border-b border-gray-100 dark:border-[#30363d]">
                {{ $header }}
            </tr>
        </thead>
    @endif

    <tbody
        @if ($reorderable)
            x-on:end.stop="$wire.reorderTable($event.target.sortable.toArray())"
            x-sortable
            data-sortable-animation-duration="{{ $reorderAnimationDuration }}"
        @endif
        class="divide-y divide-gray-50 dark:divide-[#21262d]/50"
    >
        {{ $slot }}
    </tbody>

    @if ($footer)
        <tfoot class="bg-transparent border-t border-gray-100 dark:border-[#30363d]">
            <tr>
                {{ $footer }}
            </tr>
        </tfoot>
    @endif
</table>
