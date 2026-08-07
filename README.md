WebSms Notifier
==================

Provides [WebSms](https://websms.at) integration for Symfony Notifier.

The transport uses the WebSms [Messaging REST API](https://developer.linkmobility.eu/sms-api/rest-api):
`POST /rest/smsmessaging/text` with a UTF-8 JSON payload. Recipient phone numbers must use the
international E.164 format (for example, `+436991234567`).

DSN example
-----------

```
WEBSMS_DSN=websms://CLIENT_ID:API_KEY@default?test_mode=TEST_MODE
```

where:
- `CLIENT_ID` is your email
- `API_KEY` is your WebSms API password
- `TEST_MODE` (Optional) the test parameter is used during system connection testing.
  Possible values: 0 (real SMS sent), 1 (test SMS, will not be delivered to the phone and will not be charged)

Message options
---------------

The sender and provider-specific options can be configured on an individual message:

```php
use Okorneliuk\Symfony\NotifierBridge\WebSms\WebSmsOptions;
use Symfony\Component\Notifier\Message\SmsMessage;

$options = (new WebSmsOptions)
    ->contentCategory('informational')
    ->maxSmsPerMessage(2)
    ->senderAddressType('alphanumeric')
    ->validityPeriod(300);

$message = new SmsMessage('+436991234567', 'Hello!', 'MyCompany', $options);
$texter->send($message);
```

Available options are `clientMessageId`, `contentCategory`, `maxSmsPerMessage`, `messageType`,
`notificationCallbackUrl`, `priority`, `sendAsFlashSms`, `senderAddressType`, and `validityPeriod`.
The documented values for `contentCategory`, `messageType`, and `senderAddressType` are exposed as
PHPDoc literal-string types for IDE autocomplete and static analysis while remaining regular strings
at runtime to support new values, introduced by the provider.

Then add a service:

```yaml
# config/notifier.yaml
# ...
services:
    notifier.transport_factory.websms:
        class: 'Okorneliuk\Symfony\NotifierBridge\WebSms\WebSmsTransportFactory'
        parent: 'notifier.transport_factory.abstract'
        tags: ['texter.transport_factory']
```
