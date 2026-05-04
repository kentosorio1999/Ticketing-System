<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Quick Actions
        </x-slot>

        <x-slot name="description">
            Common actions for your role
        </x-slot>

        <div class="grid gap-3">
            @foreach ($this->getQuickActions() as $action)
                <a
                    href="{{ $action['url'] }}"
                    class="group flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-primary-300 hover:bg-primary-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-primary-600 dark:hover:bg-gray-800"
                >
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300">
                        <x-dynamic-component
                            :component="$action['icon']"
                            class="h-6 w-6"
                        />
                    </div>

                    <div class="flex-1">
                        <div class="font-semibold text-gray-950 dark:text-white">
                            {{ $action['label'] }}
                        </div>

                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $action['description'] }}
                        </div>
                    </div>

                    <x-heroicon-o-chevron-right class="h-5 w-5 text-gray-400 transition group-hover:text-primary-600" />
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>