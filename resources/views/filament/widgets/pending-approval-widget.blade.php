<x-filament-widgets::widget>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h2 class="mb-2 text-lg font-bold text-gray-900 dark:text-white">
                👋 Welcome, {{ auth()->user()->name ?? auth()->user()->email }}!
            </h2>

            <p class="mb-5 text-sm text-gray-600 dark:text-gray-400">
                Your account has been created successfully. Please wait while the super admin reviews your account.
            </p>

            <div class="inline-flex rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white">
                Pending Review
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h2 class="mb-3 text-lg font-bold text-gray-900 dark:text-white">
                📋 Application Status
            </h2>

            <div class="mb-4">
                <span class="inline-flex rounded-md bg-yellow-100 px-3 py-1 text-sm font-semibold text-yellow-800">
                    Pending Approval
                </span>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400">
                Your account is waiting for super admin confirmation. After approval, you will be able to see the full dashboard and create tickets.
            </p>
        </div>
    </div>
</x-filament-widgets::widget>