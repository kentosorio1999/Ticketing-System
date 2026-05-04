<x-filament-panels::page>
    @php
        $stats = $this->getReportStats();
        $categories = $this->getTicketsByCategory();
        $units = $this->getTicketsByUnit();
        $resolvedTickets = $this->getRecentResolvedTickets();
    @endphp

    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                Ticket Reports
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Summary of ticket activity and service desk performance.
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-5">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm text-gray-500">Total Tickets</p>
                <p class="mt-2 text-3xl font-bold">{{ $stats['total'] }}</p>
            </div>

            <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 shadow-sm dark:border-blue-900 dark:bg-blue-950">
                <p class="text-sm text-blue-700 dark:text-blue-300">New Tickets</p>
                <p class="mt-2 text-3xl font-bold text-blue-700 dark:text-blue-300">{{ $stats['new'] }}</p>
            </div>

            <div class="rounded-xl border border-green-200 bg-green-50 p-5 shadow-sm dark:border-green-900 dark:bg-green-950">
                <p class="text-sm text-green-700 dark:text-green-300">Open Tickets</p>
                <p class="mt-2 text-3xl font-bold text-green-700 dark:text-green-300">{{ $stats['open'] }}</p>
            </div>

            <div class="rounded-xl border border-orange-200 bg-orange-50 p-5 shadow-sm dark:border-orange-900 dark:bg-orange-950">
                <p class="text-sm text-orange-700 dark:text-orange-300">In Progress</p>
                <p class="mt-2 text-3xl font-bold text-orange-700 dark:text-orange-300">{{ $stats['in_progress'] }}</p>
            </div>

            <div class="rounded-xl border border-red-200 bg-red-50 p-5 shadow-sm dark:border-red-900 dark:bg-red-950">
                <p class="text-sm text-red-700 dark:text-red-300">Urgent Tickets</p>
                <p class="mt-2 text-3xl font-bold text-red-700 dark:text-red-300">{{ $stats['urgent'] }}</p>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                    Ticket Status Summary
                </h3>

                <div class="mt-5 space-y-3">
                    @foreach ([
                        'New' => $stats['new'],
                        'Open' => $stats['open'],
                        'In Progress' => $stats['in_progress'],
                        'Pending' => $stats['pending'],
                        'Resolved' => $stats['resolved'],
                        'Closed' => $stats['closed'],
                        'Unassigned' => $stats['unassigned'],
                    ] as $label => $value)
                        <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $label }}</span>
                            <span class="text-sm font-bold text-gray-950 dark:text-white">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                    Tickets by Category
                </h3>

                <div class="mt-5 space-y-3">
                    @forelse ($categories as $category)
                        <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $category['category'] }}</span>
                            <span class="text-sm font-bold text-gray-950 dark:text-white">{{ $category['total'] }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No category data available.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                    Tickets by Unit
                </h3>

                <div class="mt-5 space-y-3">
                    @forelse ($units as $unit)
                        <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $unit['unit'] }}</span>
                            <span class="text-sm font-bold text-gray-950 dark:text-white">{{ $unit['total'] }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No unit data available.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                    Recently Resolved Tickets
                </h3>

                <div class="mt-5 overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">Ticket</th>
                                <th class="px-4 py-3">Owner</th>
                                <th class="px-4 py-3">Unit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse ($resolvedTickets as $ticket)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-950 dark:text-white">
                                        {{ $ticket['title'] }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                        {{ $ticket['owner']['name'] ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                        {{ $ticket['unit']['name'] ?? 'N/A' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-5 text-center text-gray-500">
                                        No resolved tickets yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>