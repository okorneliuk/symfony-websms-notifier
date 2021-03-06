WebSms Notifier
==================

Provides [WebSms](https://websms.at) integration for Symfony Notifier.

DSN example
-----------

```
WEBSMS_DSN=websms://CLIENT_ID:API_KEY@default?test_mode=TEST_MODE
```

where:
- `CLIENT_ID` is your email
- `API_KEY` is your WebSms password
- `TEST_MODE` (Optional) the test parameter is used during system connection testing.
  Possible values: 0 (real SMS sent), 1 (test SMS, will not be delivered to the phone and will not be charged)

Then add a service:

```yaml
# app/config/notifier.yml
services:
    notifier.transport_factory.websms:
        class: 'Okorneliuk\Symfony\NotifierBridge\WebSms\WebSmsTransportFactory'
        parent: 'notifier.transport_factory.abstract'
        tags: ['texter.transport_factory']
```