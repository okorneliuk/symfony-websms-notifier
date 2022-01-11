<?php

/*
 * (c) Oleh Korneliuk <oleh.korneliuk@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Okorneliuk\Symfony\NotifierBridge\WebSms;

use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Exception\UnsupportedMessageTypeException;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Oleh Korneliuk <oleh.korneliuk@gmail.com>
 */
final class WebSmsTransport extends AbstractTransport
{
    protected const HOST = 'api.websms.com';

    private $uid;
    private $apiKey;
    private $testMode;

    public function __construct(string $uid, string $apiKey, bool $testMode, HttpClientInterface $client = null, EventDispatcherInterface $dispatcher = null)
    {
        $this->uid = $uid;
        $this->apiKey = $apiKey;
        $this->testMode = $testMode;

        parent::__construct($client, $dispatcher);
    }

    public function __toString(): string
    {
        if ($this->testMode) {
            return sprintf('websms://%s?test_mode=%s', $this->getEndpoint(), $this->testMode);
        }

        return sprintf('websms://%s', $this->getEndpoint());
    }

    public function supports(MessageInterface $message): bool
    {
        return $message instanceof SmsMessage;
    }

    protected function doSend(MessageInterface $message): SentMessage
    {
        if (!$message instanceof SmsMessage) {
            throw new UnsupportedMessageTypeException(__CLASS__, SmsMessage::class, $message);
        }

        $endpoint = sprintf('https://%s/rest/smsmessaging/simple?', $this->getEndpoint());

        $httpResponse = $this->client->request('GET', $endpoint, [
            'auth_basic' => [$this->uid, $this->apiKey],
            'query' => [
                'messageContent' => iconv('UTF-8', 'ISO-8859-1', $message->getSubject()),
                'test' => $this->testMode ? 1 : 0,
                'recipientAddressList' => \str_replace(['+', '-', ' '], '', $message->getPhone()),
            ],
        ]);

        try {
            \parse_str($httpResponse->getContent(), $response);
        } catch (ExceptionInterface $e) {
            throw new TransportException(
              'Unable to send the SMS. Ivalid HTTP response code from the provider.',
              $httpResponse,
              0,
              $e
            );
        }

        if ($response['statusCode'] >= 4000) {
            throw new TransportException('Unable to send the SMS. Provider responded with error: '.$response['statusMessage'], $httpResponse);
        }

        if ($response['statusCode'] >= 2000 && $response['statusCode'] < 3000) {
            $sentMessage = new SentMessage($message, (string) $this);
            $sentMessage->setMessageId($matches[1] ?? 0);

            return $sentMessage;
        }

        throw new TransportException('Unable to send the SMS[3].', $httpResponse);
    }
}
