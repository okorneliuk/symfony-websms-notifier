<?php

declare(strict_types=1);

/*
 * (c) Oleh Korneliuk <oleh.korneliuk@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Okorneliuk\Symfony\NotifierBridge\WebSms;

use Symfony\Component\Notifier\Exception\LogicException;
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
final class WebSmsTransport extends AbstractTransport implements \Stringable
{
    protected const HOST = 'api.websms.com';

    public function __construct(
        private readonly string $uid,
        #[\SensitiveParameter] private readonly string $apiKey,
        private readonly bool $testMode,
        ?HttpClientInterface $client = null,
        ?EventDispatcherInterface $dispatcher = null
    ) {
        parent::__construct($client, $dispatcher);
    }

    public function __toString(): string
    {
        $dsn = 'websms://'.$this->getEndpoint();
        if ($this->testMode) {
            return $dsn.'?test_mode=1';
        }

        return $dsn;
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

        if (false === $messageText = iconv('UTF-8', 'ISO-8859-1', $message->getSubject())) {
            throw new LogicException('Failed to convert encoding of the message. Please review the message.');
        }

        $endpoint = sprintf('https://%s/rest/smsmessaging/simple?', $this->getEndpoint());

        $httpResponse = $this->client->request('GET', $endpoint, [
            'auth_basic' => [$this->uid, $this->apiKey],
            'query' => [
                'messageContent' => $messageText,
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
            if (is_string($response['transferId'] ?? null)) {
                $sentMessage->setMessageId($response['transferId']);
            }

            return $sentMessage;
        }

        throw new TransportException('Unable to send the SMS[3].', $httpResponse);
    }
}
