<?php

namespace Apidrop\PinPoint;

use Illuminate\Mail\MailServiceProvider;
use Illuminate\Support\ServiceProvider;
use Apidrop\PinPoint\Transport\PinpointMailAddedTransportManager;

class PinpointMailServiceProvider extends MailServiceProvider {

    public function boot() {
        $this->publishes([
            __DIR__ . '/config/pinpoint.php' => config_path('pinpoint.php')
                ], 'pinpoint');
    }

    protected function registerSwiftTransport() {
        $this->app->singleton('swift.transport', function ($app) {
            return new PinpointMailAddedTransportManager($app);
        });
    }

}
