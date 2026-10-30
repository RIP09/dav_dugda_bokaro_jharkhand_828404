<?php

namespace Website;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Website\Support\Settings;

/**
 * DAV Public School, Dugda — public website + Website CMS.
 * Loaded by the backend (bootstrap/providers.php). Public site at "/", CMS at "/website-admin".
 */
class WebsiteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/config/website.php', 'website');

        // Website-admin login: its own guard and its own table (site_admins), separate from the school-management users.
        config([
            'auth.guards.webadmin' => ['driver' => 'session', 'provider' => 'site_admins'],
            'auth.providers.site_admins' => ['driver' => 'eloquent', 'model' => \Website\Modules\Users\SiteAdmin::class],
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([Console\InstallCommand::class]);
        }
    }

    public function boot(): void
    {
        // In production behind a proxy/edge the request host can be an internal address; when APP_URL is an
        // https address, every generated link and asset uses it (works with a cached config too).
        if (config('app.env') === 'production' && str_starts_with((string) config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceRootUrl(rtrim(config('app.url'), '/'));
            \Illuminate\Support\Facades\URL::forceScheme('https');
            // Also make the request itself carry the public address, so URLs built from it (the login page's
            // "intended" URL, back() after a wrong password or a validation error) never point at the internal host.
            if (! $this->app->runningInConsole()) {
                $req = $this->app['request'];
                $host = (string) parse_url(config('app.url'), PHP_URL_HOST);
                $req->headers->set('host', $host);
                $req->server->set('HTTP_HOST', $host);
                $req->server->set('HTTPS', 'on');
                $req->server->set('SERVER_PORT', 443);
            }
        }
        $root = dirname(__DIR__);
        $this->loadViewsFrom($root.'/resources/views', 'website');
        $this->loadMigrationsFrom($root.'/database/migrations');
        $this->loadRoutesFrom($root.'/routes/web.php');
        $this->publishes([$root.'/public-assets' => public_path('site-assets')], 'website-assets');

        Blade::directive('icon', fn ($e) => "<?php echo \\Website\\Support\\Icons::svg($e); ?>");

        View::composer(['website::layouts.site', 'website::site.*'], function ($view) {
            static $shared = null;
            if ($shared === null) {
                $shared = [
                    's' => Settings::all(),
                    'menu' => \Website\Support\SiteData::menu('header'),
                    'footerMenu' => \Website\Support\SiteData::menu('footer'),
                ];
            }
            $view->with($shared);
        });
        View::composer('website::layouts.cms', function ($view) {
            $view->with('cmsCounts', \Website\Support\SiteData::inboxCounts());
        });
    }
}
