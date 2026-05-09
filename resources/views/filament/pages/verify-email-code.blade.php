<x-filament-panels::page>
    <div class="mx-auto w-full max-w-md">
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold tracking-tight">
                Verify Your Email
            </h1>

            <p class="mt-2 text-sm text-gray-500">
                We sent a 6-digit verification code to your email address.
            </p>
        </div>

        <form wire:submit="verify" class="space-y-4">
            {{ $this->form }}

            <x-filament::button type="submit" class="w-full">
                Verify Email
            </x-filament::button>
        </form>

        <div class="mt-4 text-center">
            <p class="mb-2 text-sm text-gray-500">
                Did not receive the code or code expired?
            </p>

            <x-filament::button
                type="button"
                color="gray"
                wire:click="resendCode"
                class="w-full"
            >
                Send OTP Again
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>