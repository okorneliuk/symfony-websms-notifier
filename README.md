WebSms Notifier
==================

Provides [WebSms](https://websms.at) integration for Symfony Notifier.

DSN example
-----------

```
WEBSMS_DSN=websms://CLIENT_ID:CLIENT_SECRET@default?test_mode=0
```

where:
- `CLIENT_ID` is your email
- `API_KEY` is your WebSms password
- `TEST_MODE` the test parameter is used during system connection testing.
  Possible values: 0 (real SMS sent), 1 (test SMS, will not be delivered to the phone and will not be charged)
