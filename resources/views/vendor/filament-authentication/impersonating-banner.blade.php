@if (app()->bound('impersonate') && app('impersonate')->isImpersonating())
    <div
        id="impersonating-banner"
        class="fixed top-0 left-0 right-0 z-50 flex items-center justify-center gap-4 bg-gray-800 px-4 py-2 text-sm text-white"
    >
        <span>
            Impersonating user <strong>{{ auth()->user()->name ?? auth()->user()->email }}</strong>
        </span>

        <a
            href="{{ url('/admin/leave-impersonation') }}"
            onclick="window.location.assign(this.href); return false;"
            class="rounded-md bg-gray-200 px-4 py-1 text-sm font-medium text-gray-900 hover:bg-gray-300"
        >
            Leave
        </a>
    </div>
@endif