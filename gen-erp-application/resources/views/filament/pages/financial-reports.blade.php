<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex gap-4">
            <button wire:click="$set('activeTab', 'trial_balance')" class="px-4 py-2 rounded {{ $activeTab === 'trial_balance' ? 'bg-primary-600 text-white' : 'bg-gray-100' }}">
                {{ __('Trial Balance') }}
            </button>
            <button wire:click="$set('activeTab', 'profit_loss')" class="px-4 py-2 rounded {{ $activeTab === 'profit_loss' ? 'bg-primary-600 text-white' : 'bg-gray-100' }}">
                {{ __('Profit & Loss') }}
            </button>
            <button wire:click="$set('activeTab', 'balance_sheet')" class="px-4 py-2 rounded {{ $activeTab === 'balance_sheet' ? 'bg-primary-600 text-white' : 'bg-gray-100' }}">
                {{ __('Balance Sheet') }}
            </button>
        </div>

        @if($activeTab === 'trial_balance' || $activeTab === 'balance_sheet')
            <div class="flex gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium">{{ __('As Of Date') }}</label>
                    <input type="date" wire:model="asOfDate" class="mt-1 rounded-md border-gray-300" />
                </div>
                <button wire:click="generateReport" class="px-4 py-2 bg-primary-600 text-white rounded-md">
                    {{ __('Generate') }}
                </button>
            </div>
        @else
            <div class="flex gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium">{{ __('From') }}</label>
                    <input type="date" wire:model="fromDate" class="mt-1 rounded-md border-gray-300" />
                </div>
                <div>
                    <label class="block text-sm font-medium">{{ __('To') }}</label>
                    <input type="date" wire:model="toDate" class="mt-1 rounded-md border-gray-300" />
                </div>
                <button wire:click="generateReport" class="px-4 py-2 bg-primary-600 text-white rounded-md">
                    {{ __('Generate') }}
                </button>
            </div>
        @endif

        @if(!empty($reportData))
            <div class="bg-white rounded-lg shadow p-6">
                @if($activeTab === 'trial_balance' && isset($reportData['accounts']))
                    <h3 class="text-lg font-semibold mb-4">{{ __('Trial Balance') }}</h3>
                    <table class="w-full">
                        <thead><tr><th class="text-left">{{ __('Code') }}</th><th class="text-left">{{ __('Account') }}</th><th class="text-right">{{ __('Debit') }}</th><th class="text-right">{{ __('Credit') }}</th></tr></thead>
                        <tbody>
                            @foreach($reportData['accounts'] as $row)
                                <tr><td>{{ $row['code'] }}</td><td>{{ $row['name'] }}</td><td class="text-right">৳{{ number_format($row['debit']/100,2) }}</td><td class="text-right">৳{{ number_format($row['credit']/100,2) }}</td></tr>
                            @endforeach
                        </tbody>
                        <tfoot><tr class="font-bold border-t"><td colspan="2">{{ __('Total') }}</td><td class="text-right">৳{{ number_format($reportData['total_debit']/100,2) }}</td><td class="text-right">৳{{ number_format($reportData['total_credit']/100,2) }}</td></tr></tfoot>
                    </table>
                @endif

                @if($activeTab === 'profit_loss' && isset($reportData['income']))
                    <h3 class="text-lg font-semibold mb-4">{{ __('Profit & Loss') }}</h3>
                    <h4 class="font-medium mt-4">{{ __('Income') }}</h4>
                    @foreach($reportData['income'] as $item)
                        <div class="flex justify-between py-1"><span>{{ $item['name'] }}</span><span>৳{{ number_format($item['amount']/100,2) }}</span></div>
                    @endforeach
                    <div class="flex justify-between py-1 font-semibold border-t"><span>{{ __('Total Income') }}</span><span>৳{{ number_format($reportData['total_income']/100,2) }}</span></div>
                    <h4 class="font-medium mt-4">{{ __('Expenses') }}</h4>
                    @foreach($reportData['expenses'] as $item)
                        <div class="flex justify-between py-1"><span>{{ $item['name'] }}</span><span>৳{{ number_format($item['amount']/100,2) }}</span></div>
                    @endforeach
                    <div class="flex justify-between py-1 font-semibold border-t"><span>{{ __('Total Expenses') }}</span><span>৳{{ number_format($reportData['total_expenses']/100,2) }}</span></div>
                    <div class="flex justify-between py-2 font-bold text-lg border-t-2 mt-2"><span>{{ __('Net Profit') }}</span><span>৳{{ number_format($reportData['net_profit']/100,2) }}</span></div>
                @endif

                @if($activeTab === 'balance_sheet' && isset($reportData['assets']))
                    <h3 class="text-lg font-semibold mb-4">{{ __('Balance Sheet') }}</h3>
                    <h4 class="font-medium">{{ __('Assets') }}</h4>
                    @foreach($reportData['assets'] as $item)
                        <div class="flex justify-between py-1"><span>{{ $item['name'] }}</span><span>৳{{ number_format($item['balance']/100,2) }}</span></div>
                    @endforeach
                    <div class="flex justify-between py-1 font-semibold border-t"><span>{{ __('Total Assets') }}</span><span>৳{{ number_format($reportData['total_assets']/100,2) }}</span></div>
                    <h4 class="font-medium mt-4">{{ __('Liabilities') }}</h4>
                    @foreach($reportData['liabilities'] as $item)
                        <div class="flex justify-between py-1"><span>{{ $item['name'] }}</span><span>৳{{ number_format($item['balance']/100,2) }}</span></div>
                    @endforeach
                    <div class="flex justify-between py-1 font-semibold border-t"><span>{{ __('Total Liabilities') }}</span><span>৳{{ number_format($reportData['total_liabilities']/100,2) }}</span></div>
                    <h4 class="font-medium mt-4">{{ __('Equity') }}</h4>
                    @foreach($reportData['equity'] as $item)
                        <div class="flex justify-between py-1"><span>{{ $item['name'] }}</span><span>৳{{ number_format($item['balance']/100,2) }}</span></div>
                    @endforeach
                    <div class="flex justify-between py-1 font-semibold border-t"><span>{{ __('Total Equity') }}</span><span>৳{{ number_format($reportData['total_equity']/100,2) }}</span></div>
                    @if(!$reportData['balanced'])
                        <div class="mt-4 p-3 bg-red-100 text-red-700 rounded">{{ __('Warning: Assets ≠ Liabilities + Equity') }}</div>
                    @endif
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
