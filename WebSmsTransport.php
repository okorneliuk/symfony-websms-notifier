<?php

declare(strict_types=1);

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
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
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
        return $message instanceof SmsMessage && (null === $message->getOptions() || $message->getOptions() instanceof WebSmsOptions);
    }

    protected function doSend(MessageInterface $message): SentMessage
    {
        if (!$message instanceof SmsMessage) {
            throw new UnsupportedMessageTypeException(__CLASS__, SmsMessage::class, $message);
        }

        $options = $message->getOptions()?->toArray() ?? [];
        $options['messageContent'] = $message->getSubject();
        $options['recipientAddressList'] = [$message->getPhone()];
        $options['test'] = $this->testMode;

        if (null !== $message->getFrom()) {
            $options['senderAddress'] = $message->getFrom();
        }

        $endpoint = sprintf('https://%s/rest/smsmessaging/text', $this->getEndpoint());

        $httpResponse = $this->client->request('POST', $endpoint, [
            'auth_basic' => [$this->uid, $this->apiKey],
            'headers' => [
                'Accept' => 'application/json',
            ],
            'json' => $options,
        ]);

        try {
            $httpStatusCode = $httpResponse->getStatusCode();
        } catch (TransportExceptionInterface $e) {
            throw new TransportException('Could not reach the remote WebSms server.', $httpResponse, 0, $e);
        }

        try {
            $response = $httpResponse->toArray(false);
        } catch (TransportExceptionInterface $e) {
            throw new TransportException('Could not reach the remote WebSms server.', $httpResponse, 0, $e);
        } catch (DecodingExceptionInterface $e) {
            if (200 !== $httpStatusCode) {
                throw new TransportException(\sprintf('Unable to send the SMS. Provider returned HTTP status %d.', $httpStatusCode), $httpResponse, 0, $e);
            }

            throw new TransportException('Unable to send the SMS. The provider returned an invalid JSON response.', $httpResponse, 0, $e);
        }

        if (200 !== $httpStatusCode) {
            $statusMessage = \is_string($response['statusMessage'] ?? null) ? $response['statusMessage'] : 'Unknown provider error';

            throw new TransportException(\sprintf('Unable to send the SMS. Provider returned HTTP status %d: %s.', $httpStatusCode, $statusMessage), $httpResponse);
        }

        $statusCode = $response['statusCode'] ?? null;
        if (!\is_int($statusCode)) {
            throw new TransportException('Unable to send the SMS. The provider response does not contain a valid status code.', $httpResponse);
        }

        if (!\in_array($statusCode, [2000, 2001], true)) {
            $statusMessage = \is_string($response['statusMessage'] ?? null) ? $response['statusMessage'] : 'Unknown provider error';

            throw new TransportException(\sprintf('Unable to send the SMS. Provider responded with error %d: %s.', $statusCode, $statusMessage), $httpResponse);
        }

        $transferId = $response['transferId'] ?? null;
        if (!\is_string($transferId) || '' === $transferId) {
            throw new TransportException('Unable to send the SMS. The provider response does not contain a valid transfer ID.', $httpResponse);
        }

        $sentMessage = new SentMessage($message, (string) $this);
        $sentMessage->setMessageId($transferId);

        return $sentMessage;
    }
}
