<?php

/*
 * (c) Oleh Korneliuk <oleh.korneliuk@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Okorneliuk\Symfony\NotifierBridge\WebSms\Tests;

use Okorneliuk\Symfony\NotifierBridge\WebSms\WebSmsTransport;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Test\TransportTestCase;
use Symfony\Component\Notifier\Transport\TransportInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class WebSmsTransportTest extends TransportTestCase
{
    /**
     * @return WebSmsTransport
     */
    public function createTransport(?HttpClientInterface $client = null): TransportInterface
    {
        return new WebSmsTransport('uid', 'api_key', true, $client ?? $this->createMock(HttpClientInterface::class));
    }

    public function toStringProvider(): iterable
    {
        yield ['websms://api.websms.com?test_mode=1', $this->createTransport()];
    }

    public function supportedMessagesProvider(): iterable
    {
        yield [new SmsMessage('0611223344', 'Hello!')];
    }

    public function unsupportedMessagesProvider(): iterable
    {
        yield [new ChatMessage('Hello!')];
        yield [$this->createMock(MessageInterface::class)];
    }
}
