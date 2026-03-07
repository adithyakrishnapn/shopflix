<?php

namespace Webkul\Shop\Http\Middleware;

use Closure;
use Webkul\Core\Repositories\LocaleRepository;

class Locale
{
    /**
     * Create a middleware instance.
     *
     * @return void
     */
    public function __construct(protected LocaleRepository $localeRepository) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $channel = core()->getCurrentChannel();

        if (! $channel) {
            app()->setLocale(config('app.locale'));

            return $next($request);
        }

        $locales = $channel->locales->pluck('code')->toArray();
        $localeCode = core()->getRequestedLocaleCode('locale', false);

        if (! $localeCode || ! in_array($localeCode, $locales)) {
            $localeCode = session()->get('locale');
        }

        if (! $localeCode || ! in_array($localeCode, $locales)) {
            $localeCode = $channel->default_locale?->code;
        }

        if (! $localeCode) {
            $localeCode = config('app.locale');
        }

        app()->setLocale($localeCode);
        session()->put('locale', $localeCode);
        unset($request['locale']);

        return $next($request);
    }
}
