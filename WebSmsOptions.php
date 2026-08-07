<?php

declare(strict_types=1);

/*
 * (c) Oleh Korneliuk <oleh.korneliuk@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Okorneliuk\Symfony\NotifierBridge\WebSms;

use Symfony\Component\Notifier\Message\MessageOptionsInterface;

final class WebSmsOptions implements MessageOptionsInterface
{
    private array $options = [];

    public function getRecipientId(): ?string
    {
        return null;
    }

    public function clientMessageId(string $clientMessageId): static
    {
        $this->options['clientMessageId'] = $clientMessageId;

        return $this;
    }

    /**
     * @param 'informational'|'advertisement' $contentCategory
     */
    public function contentCategory(string $contentCategory): static
    {
        $this->options['contentCategory'] = $contentCategory;

        return $this;
    }

    public function maxSmsPerMessage(int $maxSmsPerMessage): static
    {
        $this->options['maxSmsPerMessage'] = $maxSmsPerMessage;

        return $this;
    }

    /**
     * @param 'default'|'voice' $messageType
     */
    public function messageType(string $messageType): static
    {
        $this->options['messageType'] = $messageType;

        return $this;
    }

    public function notificationCallbackUrl(string $notificationCallbackUrl): static
    {
        $this->options['notificationCallbackUrl'] = $notificationCallbackUrl;

        return $this;
    }

    public function priority(int $priority): static
    {
        $this->options['priority'] = $priority;

        return $this;
    }

    public function sendAsFlashSms(bool $sendAsFlashSms = true): static
    {
        $this->options['sendAsFlashSms'] = $sendAsFlashSms;

        return $this;
    }

    /**
     * @param 'national'|'international'|'alphanumeric'|'shortcode' $senderAddressType
     */
    public function senderAddressType(string $senderAddressType): static
    {
        $this->options['senderAddressType'] = $senderAddressType;

        return $this;
    }

    public function validityPeriod(int $validityPeriod): static
    {
        $this->options['validityPeriode'] = $validityPeriod;

        return $this;
    }

    public function toArray(): array
    {
        return $this->options;
    }
}
