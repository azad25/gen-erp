<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between w-full">
                <span>Activity</span>
                <div style="font-size:10px; font-family:var(--font-mono, monospace); background:rgba(34,197,94,0.08); color:#16a34a; padding:3px 8px; border-radius:10px; font-weight:600; letter-spacing:0.5px;">
                    ● LIVE
                </div>
            </div>
        </x-slot>

        <style>
            .generp-activity-list { padding: 0; }
            .generp-activity-item {
                display: flex;
                align-items: flex-start;
                gap: 12px;
                padding: 10px 0;
                border-bottom: 1px solid #f1f5f9;
            }
            .generp-activity-item:last-child { border-bottom: none; padding-bottom: 0; }
            .generp-activity-item:first-child { padding-top: 0; }
            .generp-activity-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                margin-top: 5px;
                flex-shrink: 0;
            }
            .generp-activity-dot.green { background: #22c55e; }
            .generp-activity-dot.blue { background: #2563eb; }
            .generp-activity-dot.amber { background: #f59e0b; }
            .generp-activity-dot.red { background: #ef4444; }
            .generp-activity-dot.purple { background: #8b5cf6; }
            .generp-activity-text {
                font-size: 12px;
                color: #64748b;
                line-height: 1.4;
            }
            .generp-activity-text strong {
                color: #0f172a;
                font-weight: 600;
            }
            .generp-activity-time {
                font-size: 10px;
                color: #94a3b8;
                font-family: var(--font-mono, monospace);
                margin-top: 2px;
            }
        </style>

        <div class="generp-activity-list">
            @php
                $activities = \App\Models\AuditLog::with('user')
                    ->where('company_id', \App\Services\CompanyContext::active()->id ?? null)
                    ->latest()
                    ->limit(7)
                    ->get();
            @endphp
            @forelse($activities as $activity)
                <div class="generp-activity-item">
                    <div class="generp-activity-dot {{ match($activity->action) {
                        'created' => 'green',
                        'updated' => 'blue',
                        'deleted' => 'red',
                        default => 'purple'
                    } }}"></div>
                    <div class="flex-1">
                        <div class="generp-activity-text">
                            <strong>{{ $activity->user->name ?? 'System' }}</strong> {{ $activity->action }} a {{ class_basename($activity->entity_type) }}
                        </div>
                        <div class="generp-activity-time">{{ $activity->created_at->diffForHumans() }}</div>
                    </div>
                </div>
            @empty
                <div style="padding: 20px; text-align: center; color: #94a3b8; font-size: 12px;">No recent activity.</div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
