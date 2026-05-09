<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActiveForAdminPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user) {
            return $next($request);
        }

        $adminPath = trim(Filament::getCurrentPanel()?->getPath() ?? 'admin', '/');

        /*
         * Allow these routes to avoid redirect loop.
         */
        if (
            $request->is($adminPath . '/verify-email-code') ||
            $request->is($adminPath . '/verify-email-code/*') ||
            $request->is($adminPath . '/logout') ||
            $request->is('livewire/*') ||
            $request->is($adminPath . '/livewire/*')
        ) {
            return $next($request);
        }

        /*
         * If email is not verified, force user to OTP page first.
         */
        if (! $user->email_verified_at) {
            return redirect('/' . $adminPath . '/verify-email-code');
        }

        /*
         * If is_active column does not exist, allow access.
         */
        if (! array_key_exists('is_active', $user->getAttributes())) {
            return $next($request);
        }

        /*
         * Active users can access normally.
         */
        if ((bool) $user->is_active) {
            return $next($request);
        }

        /*
         * Super Admin/Admin/Staff can access normally.
         */
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        if (method_exists($user, 'hasAnyRole')) {
            if ($user->hasAnyRole([
                'Super Admin',
                'super_admin',
                'Admin',
                'admin',
                'Admin Unit',
                'Staff Unit',
            ])) {
                return $next($request);
            }
        }

        /*
         * Pending normal users can only access the dashboard.
         * Dashboard will show Pending Approval widget.
         */
        if (
            $request->is($adminPath) ||
            $request->is($adminPath . '/')
        ) {
            return $next($request);
        }

        return redirect(Filament::getUrl());
    }
}