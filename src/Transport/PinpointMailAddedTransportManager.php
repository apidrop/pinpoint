<?php

namespace Apidrop\PinPoint\Transport;

use Illuminate\Mail\TransportManager;

class PinpointMailAddedTransportManager extends TransportManager {

    protected function createPinpointDriver() {
        return new PinpointMailTransport;
    }

}
