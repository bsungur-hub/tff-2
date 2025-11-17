<?php

namespace App\Providers;

use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $allowedIPs = array_map('trim', explode(',', config('app.debug_allowed_ips')));

        $allowedIPs = array_filter($allowedIPs);

        if (empty($allowedIPs)) {
            return;
        }

        if (in_array(Request::ip(), $allowedIPs)) {
            Debugbar::enable();
        } else {
            Debugbar::disable();
        }
    }

    /**
     * Bootstrap any application services.
     */

    public function boot(): void
    {
        ParallelTesting::setUpTestDatabase(function (string $database, int $token) {
            Artisan::call('db:seed');
        });

        // Kategori sayfaları için kullanıcı grubu kontrolü
        View::composer('shop::categories.*', function ($view) {
            $category = $view->category ?? null;
            $user = Auth::guard('customer')->user();

            if ($category && $category->id == 2) { // Anatolia kategorisi
                if (! $user || $user->customer_group_id != 4) { // Anatolia grubunda değilse
                    abort(404, '404 page not found!');
                }
            }
        });
    }
}
