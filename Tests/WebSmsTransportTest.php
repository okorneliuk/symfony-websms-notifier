<?php

/*
 * (c) Oleh Korneliuk <oleh.korneliuk@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Okorneliuk\Symfony\NotifierBridge\WebSms\Tests;

use Okorneliuk\Symfony\NotifierBridge\WebSms\WebSmsTransport;
use Okorneliuk\Symfony\NotifierBridge\WebSms\WebSmsOptions;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\MessageOptionsInterface;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Test\TransportTestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class WebSmsTransportTest extends TransportTestCase
{
    public static function createTransport(?HttpClientInterface $client = null, bool $testMode = true): WebSmsTransport
    {
        return new WebSmsTransport('uid', 'api_key', $testMode, $client ?? new MockHttpClient());
    }

    public static function toStringProvider(): iterable
    {
        yield ['websms://api.websms.com?test_mode=1', self::createTransport()];
    }

    public static function supportedMessagesProvider(): iterable
    {
        yield [new SmsMessage('+436991234567', 'Hello!')];
        yield [new SmsMessage('+436991234567', 'Hello!', 'WebSms', new WebSmsOptions())];
    }

    public static function unsupportedMessagesProvider(): iterable
    {
        yield [new ChatMessage('Hello!')];
        yield [new SmsMessage('+436991234567', 'Hello!', null, new class implements MessageOptionsInterface {
            public function getRecipientId(): ?string
            {
                return null;
            }

            public function toArray(): array
            {
                return [];
            }
        })];
    }

    public function testSendUsesJsonApiAndPreservesUtf8Message(): void
    {
        $client = new MockHttpClient(static function (string $method, string $url, array $options): MockResponse {
            self::assertSame('POST', $method);
            self::assertSame('https://api.websms.com/rest/smsmessaging/text', $url);
            self::assertSame('Accept: application/json', $options['normalized_headers']['accept'][0] ?? null);
            self::assertSame('Content-Type: application/json', $options['normalized_headers']['content-type'][0] ?? null);
            self::assertSame('Authorization: Basic '.base64_encode('uid:api_key'), $options['normalized_headers']['authorization'][0] ?? null);
            self::assertSame([
                'contentCategory' => 'informational',
                'maxSmsPerMessage' => 2,
                'sendAsFlashSms' => true,
                'senderAddressType' => 'alphanumeric',
                'messageContent' => 'Merhaba, İstanbul! Привіт €',
                'recipientAddressList' => ['+436991234567'],
                'test' => true,
                'senderAddress' => 'WebSms',
            ], json_decode($options['body'], true, 512, \JSON_THROW_ON_ERROR));

            return new MockResponse(json_encode([
                'smsCount' => 1,
                'statusCode' => 2000,
                'statusMessage' => 'OK',
                'transferId' => '0059d0b20100a0a8b803',
            ], \JSON_THROW_ON_ERROR), [
                'http_code' => 200,
                'response_headers' => ['content-type: application/json'],
            ]);
        });

        $options = (new WebSmsOptions())
            ->contentCategory('informational')
            ->maxSmsPerMessage(2)
            ->sendAsFlashSms()
            ->senderAddressType('alphanumeric');
        $message = new SmsMessage('+436991234567', 'Merhaba, İstanbul! Привіт €', 'WebSms', $options);

        $sentMessage = self::createTransport($client)->send($message);

        self::assertSame('0059d0b20100a0a8b803', $sentMessage->getMessageId());
    }

    public function testQueuedResponseIsSuccessful(): void
    {
        $client = new MockHttpClient(new MockResponse('{"statusCode":2001,"statusMessage":"OK_QUEUED","transferId":"queued-id"}', [
            'response_headers' => ['content-type: application/json'],
        ]));

        $sentMessage = self::createTransport($client)->send(new SmsMessage('+436991234567', 'Queued message'));

        self::assertSame('queued-id', $sentMessage->getMessageId());
    }

    public function testProviderErrorIsReported(): void
    {
        $client = new MockHttpClient(new MockResponse('{"statusCode":4009,"statusMessage":"INVALID_TEXT"}', [
            'response_headers' => ['content-type: application/json'],
        ]));

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Unable to send the SMS. Provider responded with error 4009: INVALID_TEXT.');

        self::createTransport($client)->send(new SmsMessage('+436991234567', 'Invalid message'));
    }

    public function testMalformedProviderResponseIsReported(): void
    {
        $client = new MockHttpClient(new MockResponse('not-json', [
            'response_headers' => ['content-type: application/json'],
        ]));

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Unable to send the SMS. The provider returned an invalid JSON response.');

        self::createTransport($client)->send(new SmsMessage('+436991234567', 'Hello!'));
    }

    public function testResponseWithoutStatusCodeIsReported(): void
    {
        $client = new MockHttpClient(new MockResponse('{}', [
            'response_headers' => ['content-type: application/json'],
        ]));

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Unable to send the SMS. The provider response does not contain a valid status code.');

        self::createTransport($client)->send(new SmsMessage('+436991234567', 'Hello!'));
    }

    public function testHttpErrorIsReported(): void
    {
        $client = new MockHttpClient(new MockResponse('{"statusMessage":"Unauthorized"}', [
            'http_code' => 401,
            'response_headers' => ['content-type: application/json'],
        ]));

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Unable to send the SMS. Provider returned HTTP status 401: Unauthorized.');

        self::createTransport($client)->send(new SmsMessage('+436991234567', 'Hello!'));
    }

    public function testHttpErrorWithInvalidJsonIsReported(): void
    {
        $client = new MockHttpClient(new MockResponse('Unauthorized', ['http_code' => 401]));

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Unable to send the SMS. Provider returned HTTP status 401.');

        self::createTransport($client)->send(new SmsMessage('+436991234567', 'Hello!'));
    }

    public function testResponseWithoutTransferIdIsReported(): void
    {
        $client = new MockHttpClient(new MockResponse('{"statusCode":2000,"statusMessage":"OK"}', [
            'response_headers' => ['content-type: application/json'],
        ]));

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Unable to send the SMS. The provider response does not contain a valid transfer ID.');

        self::createTransport($client)->send(new SmsMessage('+436991234567', 'Hello!'));
    }
}
