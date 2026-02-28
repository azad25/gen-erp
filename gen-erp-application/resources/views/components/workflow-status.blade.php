@props(['instance' => null])

@if ($instance)
    @php
        $status = $instance->currentStatus();
        $colorMap = [
            'gray' => '#6B7280',
            'warning' => '#F59E0B',
            'success' => '#10B981',
            'danger' => '#EF4444',
            'info' => '#3B82F6',
            'primary' => '#1B4F72',
        ];
        $bgMap = [
            'gray' => '#F3F4F6',
            'warning' => '#FEF3C7',
            'success' => '#D1FAE5',
            'danger' => '#FEE2E2',
            'info' => '#DBEAFE',
            'primary' => '#E8F0FE',
        ];
        $color = $status ? ($colorMap[$status->color] ?? $colorMap['gray']) : $colorMap['gray'];
        $bg = $status ? ($bgMap[$status->color] ?? $bgMap['gray']) : $bgMap['gray'];
    @endphp
    <div>
        <span style="
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            background-color: {{ $bg }};
            color: {{ $color }};
        ">
            {{ $status?->label ?? $instance->current_status_key }}
        </span>
    </div>
@endif
