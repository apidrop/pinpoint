# Laravel AWS Pinpoint Email Driver and SMS Sender

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

## Installation

Install this package with composer.

```sh
composer require apidrop/pinpoint
```

## Publish vendor configuration

```
php artisan vendor:publish --tag=pinpoint
```

And then add the following entries in your .env file:


```
MAIL_DRIVER=pinpoint

NOTIFICATION_EMAIL="no-reply@domain.com"

AWS_ACCESS_KEY_ID="[KEY_ID]"
AWS_SECRET_ACCESS_KEY="[SECRET_KEY]"
AWS_PINPOINT_REGION="us-east-1"
AWS_PINPOINT_SENDER_ID="[SENDER_ID]"
AWS_PINPOINT_APPLICATION_ID="[APP_ID]"
```

## License

The GPL3 License. Please see [License File](LICENSE.md) for more information.