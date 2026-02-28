<div class="flex items-center gap-2 me-4">
    <a href="{{ route('locale.set', app()->getLocale() === 'en' ? 'bn' : 'en') }}" class="flex items-center justify-center w-[34px] h-[34px] rounded-lg border border-gray-200 bg-white text-[11px] font-bold text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition-all shadow-sm ring-white ring-2 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:ring-gray-900 dark:hover:bg-gray-700 dark:hover:text-white" title="Switch Language">
        {{ strtoupper(app()->getLocale() === 'en' ? 'BN' : 'EN') }}
    </a>
</div>
