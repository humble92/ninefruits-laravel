<div class="space-y-3">
    <div class="flex items-center justify-between text-sm">
        <span class="text-gray-500 dark:text-gray-400">{{ __('graduation.reports.rows', ['count' => $rows->count()]) }}</span>
        <a href="{{ $csvUrl }}" class="underline">{{ __('graduation.reports.download_csv') }}</a>
    </div>
    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left dark:bg-white/5">
                <tr>
                    @foreach ($columns as $column)
                        <th class="whitespace-nowrap px-3 py-2 font-medium">{{ __('graduation.report_columns.'.$column) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="border-t border-gray-100 dark:border-white/5">
                        @foreach ($columns as $column)
                            <td class="whitespace-nowrap px-3 py-1.5">{{ \App\Domain\Reporting\Services\ReportCatalog::cell($row->{$column} ?? null) }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($columns) }}" class="px-3 py-6 text-center text-gray-500">{{ __('graduation.reports.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
