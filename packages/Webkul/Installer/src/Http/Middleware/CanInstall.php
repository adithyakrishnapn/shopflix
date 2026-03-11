<?php

namespace Webkul\Installer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Webkul\Installer\Helpers\DatabaseManager;

class CanInstall
{
    /**
     * Handles Requests for Installer middleware.
     *
     * @return void
     */
    public function handle(Request $request, Closure $next)
    {
        if (Str::contains($request->getPathInfo(), '/install')) {
            // Set a flag to indicate we're in installer mode
            config(['installer.mode' => true]);
            
            if ($this->isAlreadyInstalled() && ! $request->ajax()) {
                return redirect()->route('shop.home.index');
            }
        } else {
            if (! $this->isAlreadyInstalled()) {
                return redirect()->route('installer.index');
            }
        }

        return $next($request);
    }

    /**
     * Application Already Installed.
     *
     * @return bool
     */
    public function isAlreadyInstalled()
    {
        // Cream mode is treated as pre-configured setup and should not show installer UI.
        if (env('BAGISTO_INSTALLATION_TYPE') === 'cream') {
            return true;
        }

        if (file_exists(storage_path('installed'))) {
            return true;
        }

        try {
            if (app(DatabaseManager::class)->isInstalled()) {
                touch(storage_path('installed'));

                Event::dispatch('bagisto.installed');

                return true;
            }
        } catch (\Exception $e) {
            // Database not ready yet during installation
            return false;
        }

        return false;
    }
}
