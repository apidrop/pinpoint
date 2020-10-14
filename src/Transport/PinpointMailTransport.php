<?php

namespace Apidrop\PinPoint\Transport;

use Illuminate\Mail\Transport\Transport;
use Swift_Mime_SimpleMessage;
use Aws\Laravel\AwsFacade;
use Apidrop\PinPoint\Enums\PinpointEnums;

class PinpointMailTransport extends Transport {

    protected $awsConfigData = [];

    public function __construct() {
        $this->awsConfigData['access_key_id'] = config('pinpoint.access_key_id');
        $this->awsConfigData['secret_access_key'] = config('pinpoint.secret_access_key');
        $this->awsConfigData['region'] = config('pinpoint.region');
        $this->awsConfigData['sender_id'] = config('pinpoint.sender_id');
        $this->awsConfigData['application_id'] = config('pinpoint.application_id');

        if (is_null($this->awsConfigData['access_key_id'])) {
            throw new Exception('Invalid PinPoint configuration. AWS_ACCESS_KEY_ID not set');
        }

        if (is_null($this->awsConfigData['secret_access_key'])) {
            throw new Exception('Invalid PinPoint configuration. AWS_SECRET_ACCESS_KEY not set');
        }

        if (is_null($this->awsConfigData['region'])) {
            throw new Exception('Invalid PinPoint configuration. AWS_PINPOINT_REGION not set');
        }

        if (is_null($this->awsConfigData['sender_id'])) {
            //throw new Exception('Invalid PinPoint configuration. AWS_PINPOINT_SENDER_ID not set');
        }

        if (is_null($this->awsConfigData['application_id'])) {
            throw new Exception('Invalid PinPoint configuration. AWS_PINPOINT_APPLICATION_ID not set');
        }
    }

    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null) {

        $this->beforeSendPerformed($message);

        $this->awsClient = AwsFacade::createClient(
                        'pinpoint',
                        [
                            'credentials' => [
                                'key' => $this->awsConfigData['access_key_id'],
                                'secret' => $this->awsConfigData['secret_access_key'],
                            ],
                            'region' => $this->awsConfigData['region']
                        ]
        );

        $payload = $this->getBody($message);

        $response = $this->awsClient->sendMessages($payload);

        $this->sendPerformed($message);

        return $this->numberOfRecipients($message);
    }

    /**
     * Get body for the message.
     *
     * @param  \Swift_Mime_SimpleMessage  $message
     * @return array
     */
    protected function getBody(Swift_Mime_SimpleMessage $message) {
        $bodyData = [
            'ApplicationId' => $this->awsConfigData['application_id'],
            'MessageRequest' => [
                'Addresses' => $this->getTo($message),
                'MessageConfiguration' => [
                    'EmailMessage' => [
                        'FromAddress' => env('NOTIFICATION_EMAIL', 'no-reply@email.com'),
                        'ReplyToAddresses' => [env('NOTIFICATION_EMAIL', 'no-reply@email.com')],
                        'SimpleEmail' => [
                            'HtmlPart' => [
                                'Data' => $message->getBody(),
                            ],
                            'Subject' => [
                                'Data' => $message->getSubject(),
                            ]
                        ]
                    ]
                ]
            ]
        ];
        return $bodyData;
    }

    /**
     * Get the "to" payload field for the API request.
     *
     * @param  \Swift_Mime_SimpleMessage  $message
     * @return string
     */
    protected function getTo(Swift_Mime_SimpleMessage $message) {
        $addresses = [];

        foreach ($this->allContacts($message) as $email => $name) {
            $addresses[$email] = ['ChannelType' => PinpointEnums::TYPE_MESSAGE_EMAIL];
        }

        return $addresses;
    }

    /**
     * Get all of the contacts for the message.
     *
     * @param  \Swift_Mime_SimpleMessage  $message
     * @return array
     */
    protected function allContacts(Swift_Mime_SimpleMessage $message) {
        return array_merge(
                (array) $message->getTo(), (array) $message->getCc(), (array) $message->getBcc()
        );
    }

}
