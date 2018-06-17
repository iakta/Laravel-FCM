<?php

namespace LaravelFCM;

use Illuminate\Support\ServiceProvider;
use LaravelFCM\Sender\FCMGroup;
use LaravelFCM\Sender\FCMSender;
use Monolog\Handler\NullHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class FCMServiceProvider extends ServiceProvider
{
    protected $defer = true;

    public function boot()
    {
        if (str_contains($this->app->version(), 'Lumen')) {
            $this->app->configure('fcm');
        } else {
            $this->publishes([
                __DIR__ . '/../config/fcm.php' => config_path('fcm.php'),
            ]);
        }
    }

    public function register()
    {
        if (!str_contains($this->app->version(), 'Lumen')) {
            $this->mergeConfigFrom(__DIR__ . '/../config/fcm.php', 'fcm');
        }

        $this->app->singleton('fcm.client', function ($app) {
            return (new FCMManager($app))->driver();
        });

        $this->app->singleton('fcm.logger', function ($app) {
            $logger = new Logger('Laravel-FCM');
            $filename = storage_path('logs/iakta-fcm-' . php_sapi_name() . '.log');
            $logger->pushHandler(new RotatingFileHandler($filename, 15));
            return $logger;
        });

        $this->app->bind('fcm.group', function ($app) {
            $client = $app['fcm.client'];
            $url = $app['config']->get('fcm.http.server_group_url');
            $logger = $app['fcm.logger'];

            return new FCMGroup($client, $url, $logger);
        });

        $this->app->bind('fcm.sender', function ($app) {
            $client = $app['fcm.client'];
            $url = $app['config']->get('fcm.http.server_send_url');
            $logger = $app['fcm.logger'];

            return new FCMSender($client, $url, $logger);
        });
    }

    public function provides()
    {
        return ['fcm.client', 'fcm.group', 'fcm.sender', 'fcm.logger'];
    }
}
