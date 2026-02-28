<x-filament-panels::page>
    <style>
        /* ─── BOTTOM GRID ─── */
        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 16px;
            margin-top: 1rem;
        }
        @media (max-width: 1024px) {
            .bottom-grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: var(--card-bg, #ffffff);
            border: 1px solid var(--card-border, #e8ecf0);
            border-radius: 12px;
            box-shadow: var(--card-shadow, 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04));
            overflow: hidden;
            animation: fadeSlideUp 0.4s ease 0.2s both;
        }

        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--card-border, #e8ecf0);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-primary, #0f172a);
            letter-spacing: -0.2px;
        }

        .card-action {
            font-size: 11px;
            font-weight: 600;
            color: var(--accent-blue-light, #2563eb);
            cursor: pointer;
            padding: 4px 10px;
            border-radius: 6px;
            transition: background 0.12s;
        }

        .card-action:hover {
            background: rgba(37, 99, 235, 0.08);
        }

        /* ─── TABLE ─── */
        .table-wrap {
            overflow-x: auto;
        }

        .bottom-grid table {
            width: 100%;
            border-collapse: collapse;
        }

        .bottom-grid thead th {
            padding: 10px 20px;
            text-align: left;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            color: var(--text-muted, #94a3b8);
            background: #fafbfc;
            border-bottom: 1px solid var(--card-border, #e8ecf0);
            white-space: nowrap;
        }

        .bottom-grid tbody td {
            padding: 11px 20px;
            font-size: 12.5px;
            color: var(--text-secondary, #64748b);
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .bottom-grid tbody tr:last-child td {
            border-bottom: none;
        }

        .bottom-grid tbody tr:hover td {
            background: #fafbfc;
        }

        .td-primary {
            color: var(--text-primary, #0f172a) !important;
            font-weight: 600;
        }

        .generp-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: 0.1px;
        }

        .generp-badge::before {
            content: '';
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: currentColor;
            opacity: 0.7;
        }

        .badge-paid { background: rgba(34, 197, 94, 0.1); color: #16a34a; }
        .badge-pending { background: rgba(245, 158, 11, 0.12); color: #b45309; }
        .badge-overdue { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
        .badge-draft { background: rgba(100, 116, 139, 0.1); color: #475569; }

        /* ─── ACTIVITY CARD ─── */
        .activity-list {
            padding: 8px 0;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 10px 20px;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.1s;
            cursor: default;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-item:hover {
            background: #fafbfc;
        }

        .activity-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-top: 5px;
            flex-shrink: 0;
        }

        .activity-dot.green { background: #22c55e; }
        .activity-dot.blue { background: #2563eb; }
        .activity-dot.amber { background: #f59e0b; }
        .activity-dot.red { background: #ef4444; }
        .activity-dot.purple { background: #8b5cf6; }

        .activity-body {
            flex: 1;
        }

        .activity-text {
            font-size: 12px;
            color: var(--text-secondary, #64748b);
            line-height: 1.4;
        }

        .activity-text strong {
            color: var(--text-primary, #0f172a);
            font-weight: 600;
        }

        .activity-time {
            font-size: 10px;
            color: var(--text-muted, #94a3b8);
            font-family: var(--font-mono, monospace);
            margin-top: 2px;
        }

        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ─── STAT CARDS ─── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }

        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .stat-card {
            background: var(--card-bg, #ffffff);
            border: 1px solid var(--card-border, #e8ecf0);
            border-radius: 12px;
            padding: 18px 20px;
            box-shadow: var(--card-shadow, 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04));
            transition: box-shadow 0.2s;
            cursor: default;
            animation: fadeSlideUp 0.35s ease both;
        }

        .stat-card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }

        .stat-card:nth-child(1) { animation-delay: 0.05s; }
        .stat-card:nth-child(2) { animation-delay: 0.1s; }
        .stat-card:nth-child(3) { animation-delay: 0.15s; }
        .stat-card:nth-child(4) { animation-delay: 0.2s; }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .stat-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted, #94a3b8);
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-primary, #0f172a);
            letter-spacing: -0.8px;
            line-height: 1;
            margin-bottom: 6px;
            font-family: var(--font-mono, monospace);
        }

        .stat-sub {
            font-size: 11px;
            color: var(--text-muted, #94a3b8);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .stat-delta {
            font-size: 11px;
            font-weight: 600;
            padding: 1px 6px;
            border-radius: 4px;
        }

        .stat-delta.up {
            background: rgba(34, 197, 94, 0.1);
            color: #16a34a;
        }

        .stat-delta.down {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }

        /* ─── MINI CHART BARS ─── */
        .mini-chart {
            display: flex;
            align-items: flex-end;
            gap: 3px;
            height: 28px;
            margin-top: 8px;
        }
        .bar {
            flex: 1;
            border-radius: 2px 2px 0 0;
            background: #e8ecf0;
            transition: background 0.15s;
        }
        .bar:hover, .bar:last-child {
            background: #22c55e;
            opacity: 0.7;
        }
        .bar:last-child { opacity: 1; }
    </style>

    <div class="stats-grid">
        @php
            $company = \App\Services\CompanyContext::active();
            $companyId = $company ? $company->id : null;
            $revenueThisMonth = 0;
            $outstanding = 0;
            $lowStock = 0;
            $pendingApprovals = 0;

            if ($companyId) {
                // Ignore query builder inspections if they arise
                $revenueThisMonth = \App\Models\Invoice::where('company_id', $companyId)
                    ->whereMonth('invoice_date', now()->month)
                    ->where('status', '!=', 'cancelled')
                    ->sum('total_amount') ?? 0;

                $outstanding = \App\Models\Invoice::where('company_id', $companyId)
                    ->whereIn('status', ['sent', 'partial', 'overdue'])
                    ->sum('balance_due') ?? 0;

                if (class_exists(\App\Models\StockLevel::class)) {
                    $lowStock = \App\Models\StockLevel::where('company_id', $companyId)
                        ->whereRaw('(quantity - reserved_quantity) <= (
                            SELECT low_stock_threshold FROM products
                            WHERE products.id = stock_levels.product_id
                        )')
                        ->count();
                }

                if (class_exists(\App\Models\WorkflowApproval::class)) {
                    $pendingApprovals = \App\Models\WorkflowApproval::where('company_id', $companyId)
                        ->where('status', 'pending')
                        ->count();
                }
            }
        @endphp

        <!-- Revenue Card -->
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-label">REVENUE THIS MONTH</div>
            </div>
            <div class="stat-value">৳{{ number_format($revenueThisMonth / 100, 2) }}</div>
            <div class="stat-sub">
                vs last month ↗
            </div>
            <div class="mini-chart">
                <div class="bar" style="height: 40%"></div>
                <div class="bar" style="height: 55%"></div>
                <div class="bar" style="height: 35%"></div>
                <div class="bar" style="height: 70%"></div>
                <div class="bar" style="height: 60%"></div>
                <div class="bar" style="height: 80%"></div>
                <div class="bar" style="height: 95%"></div>
            </div>
        </div>

        <!-- Outstanding Card -->
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-label">OUTSTANDING</div>
            </div>
            <div class="stat-value">৳{{ number_format($outstanding / 100, 2) }}</div>
            <div class="stat-sub">
                receivables ↻
            </div>
        </div>

        <!-- Low Stock Card -->
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-label">LOW STOCK ALERTS</div>
            </div>
            <div class="stat-value">{{ $lowStock }}</div>
            <div class="stat-sub">
                products below threshold ⚠️
            </div>
        </div>

        <!-- Pending Approvals Card -->
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-label">PENDING APPROVALS</div>
            </div>
            <div class="stat-value">{{ $pendingApprovals }}</div>
            <div class="stat-sub">
                awaiting action ⏱️
            </div>
        </div>
    </div>

    <div class="bottom-grid">

        <!-- RECENT INVOICES -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Recent Invoices</div>
                <a href="{{ \App\Filament\Resources\InvoiceResource::getUrl('index') }}" class="card-action">View all →</a>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $invoices = \App\Models\Invoice::with('customer')
                                ->where('company_id', \App\Services\CompanyContext::active()->id ?? null)
                                ->latest()
                                ->limit(5)
                                ->get();
                        @endphp
                        @forelse($invoices as $invoice)
                            <tr>
                                <td class="td-primary" style="font-family:var(--font-mono, monospace); font-size:11.5px;">
                                    {{ $invoice->invoice_number }}
                                </td>
                                <td>{{ $invoice->customer->name ?? '—' }}</td>
                                <td class="td-primary" style="font-family:var(--font-mono, monospace);">
                                    ৳{{ number_format($invoice->total_amount / 100, 2) }}
                                </td>
                                <td style="font-family:var(--font-mono, monospace); font-size:11.5px; {{ $invoice->due_date && $invoice->due_date->isPast() && in_array($invoice->status->value ?? 'draft', ['sent', 'partial', 'overdue']) ? 'color:#dc2626;' : 'color:var(--text-muted, #94a3b8);' }}">
                                    {{ $invoice->due_date ? $invoice->due_date->format('d M Y') : '—' }}
                                </td>
                                <td>
                                    @php
                                        $statusClass = match($invoice->status->value ?? 'draft') {
                                            'paid' => 'paid',
                                            'partial' => 'pending',
                                            'overdue' => 'overdue',
                                            'sent' => 'pending',
                                            default => 'draft'
                                        };
                                    @endphp
                                    <span class="generp-badge badge-{{ $statusClass }}">
                                        {{ ucfirst($invoice->status->value ?? 'Draft') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px;">No recent invoices found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ACTIVITY FEED -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Activity</div>
                <div style="font-size:10px; font-family:var(--font-mono, monospace); background:rgba(34,197,94,0.08); color:#16a34a; padding:3px 8px; border-radius:10px; font-weight:600;">
                    ● LIVE
                </div>
            </div>
            <div class="activity-list">
                @php
                    $activities = \App\Models\AuditLog::with('user')
                        ->where('company_id', \App\Services\CompanyContext::active()->id ?? null)
                        ->latest()
                        ->limit(7)
                        ->get();
                @endphp
                @forelse($activities as $activity)
                    <div class="activity-item">
                        <div class="activity-dot {{ match($activity->action) {
                            'created' => 'green',
                            'updated' => 'blue',
                            'deleted' => 'red',
                            default => 'purple'
                        } }}"></div>
                        <div class="activity-body">
                            <div class="activity-text">
                                <strong>{{ $activity->user->name ?? 'System' }}</strong> {{ $activity->action }} a {{ class_basename($activity->entity_type) }}
                            </div>
                            <div class="activity-time">{{ $activity->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                @empty
                    <div style="padding: 20px; text-align: center; color: var(--text-muted, #94a3b8); font-size: 12px;">No recent activity.</div>
                @endforelse
            </div>
        </div>

    </div>
</x-filament-panels::page>
