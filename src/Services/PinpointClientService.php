<?php

namespace Apidrop\PinPoint\Services;

use Aws\Laravel\AwsFacade;
use Exception;
use Apidrop\PinPoint\Enums\PinpointEnums;
use Apidrop\PinPoint\Exceptions\ExceptionNotSendMessage;

class PinpointClientService {

    protected $awsConfigData = [];
    protected $awsClient;

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
        //dd($this->awsClient->getRegion());
    }

    public function sendSMS($addresses = [], $message = '') {
        // https://docs.aws.amazon.com/en_us/pinpoint-sms-voice/latest/APIReference/v1-sms-voice-voice-message.html

        $addressesList = $this->prepareAddresses(PinpointEnums::TYPE_MESSAGE_SMS, $addresses);

        $sendMessageConfiguration = [
            'ApplicationId' => $this->awsConfigData['application_id'],
            'MessageRequest' => [
                'Addresses' => $addressesList,
                'MessageConfiguration' => [
                    'SMSMessage' => [
                        'Body' => $message,
                        'MessageType' => 'TRANSACTIONAL',
                    ],
                ],
            ],
        ];

        if (!empty($this->awsConfigData['sender_id'])) {
            $sendMessageConfiguration['MessageRequest']['MessageConfiguration']['SMSMessage']['SenderId'] = $this->awsConfigData['sender_id'];
        }

        try {
            $retSend = $this->awsClient->sendMessages($sendMessageConfiguration);
        } catch (Exception $exception) {
            throw ExceptionNotSendMessage::serviceRespondedWithAnError($exception);
        }

        $output = $retSend->get('MessageResponse');
        dd($output);

        foreach ($output['Result'] as $number => $res) {
            if ($res['DeliveryStatus'] === 'SUCCESSFUL') {
                // Successfully delivery
                continue;
            }

            throw new Exception('Faild send ' . PinpointEnums::TYPE_MESSAGE_SMS . '. Body' . $message->body . ' Status: ' . $res['StatusMessage']);
        }
    }

    public function sendEmail($addresses = [], $subject = '', $message = '') {

        $addressesList = $this->prepareAddresses(PinpointEnums::TYPE_MESSAGE_EMAIL, $addresses);

        $sendMessageConfiguration = [
            'ApplicationId' => $this->awsConfigData['application_id'],
            'MessageRequest' => [
                'Addresses' => $addressesList,
                'MessageConfiguration' => [
                    'EmailMessage' => [
                        'FromAddress' => env('NOTIFICATION_EMAIL', 'no-reply@email.com'),
                        'ReplyToAddresses' => [env('NOTIFICATION_EMAIL', 'no-reply@email.com')],
                        'SimpleEmail' => [
                            'HtmlPart' => [
                                'Data' => $message,
                            ],
                            'Subject' => [
                                'Data' => $subject,
                            ]
                        ]
                    ]
                ]
            ]
        ];

        try {
            $retSend = $this->awsClient->sendMessages($sendMessageConfiguration);
        } catch (Exception $exception) {
            throw ExceptionNotSendMessage::serviceRespondedWithAnError($exception);
        }

        $output = $retSend->get('MessageResponse');
        dd($output);

        foreach ($output['Result'] as $number => $res) {
            if ($res['DeliveryStatus'] === 'SUCCESSFUL') {
                // Successfully delivery
                continue;
            }

            throw new Exception('Faild send ' . PinpointEnums::TYPE_MESSAGE_EMAIL . '. Body' . $message->body . ' Status: ' . $res['StatusMessage']);
        }
    }

    protected function prepareAddresses($typeMessage, $addresses) {

        if (!$addresses || !is_array($addresses)) {
            throw new Exception('No ' . $typeMessage . ' addresses');
        }

        $addressesList = [];

        foreach ($addresses as $address) {
            $addressesList[$address] = [
                'ChannelType' => $typeMessage
            ];
        }

        return $addressesList;
    }

}
