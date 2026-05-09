<x-filament-widgets::widget>
    <div class="space-y-4">
        <x-filament::section>
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    @if ($this->getAvatarUrl())
                        <img
                            src="{{ $this->getAvatarUrl() }}"
                            alt="Avatar"
                            class="rounded-full object-cover border border-gray-200 shadow-sm dark:border-gray-700"
                            style="width: 48px; height: 48px; min-width: 48px; max-width: 48px; min-height: 48px; max-height: 48px;"
                        />
                    @else
                        <div
                            class="flex items-center justify-center rounded-full bg-gray-950 text-sm font-bold text-white shadow-sm"
                            style="width: 48px; height: 48px; min-width: 48px; max-width: 48px; min-height: 48px; max-height: 48px;"
                        >
                            {{ $this->getInitials() }}
                        </div>
                    @endif

                    <div>
                        <div class="font-semibold text-gray-950 dark:text-white">
                            Welcome
                        </div>

                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $this->getUserRole() }}
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ $this->getLogoutUrl() }}">
                    @csrf

                    <x-filament::button
                        type="submit"
                        color="gray"
                        icon="heroicon-m-arrow-left-on-rectangle"
                    >
                        Sign out
                    </x-filament::button>
                </form>
            </div>
        </x-filament::section>

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
    </div>
</x-filament-widgets::widget>