<?php

declare(strict_types=1);

/*
 * (c) Oleh Korneliuk <oleh.korneliuk@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Okorneliuk\Symfony\NotifierBridge\WebSms\Tests;

use Okorneliuk\Symfony\NotifierBridge\WebSms\WebSmsOptions;
use PHPUnit\Framework\TestCase;

final class WebSmsOptionsTest extends TestCase
{
    public function testOptions(): void
    {
        $options = (new WebSmsOptions)
            ->clientMessageId('client-id')
            ->contentCategory('informational')
            ->maxSmsPerMessage(2)
            ->messageType('default')
            ->notificationCallbackUrl('https://example.com/delivery-report')
            ->priority(5)
            ->sendAsFlashSms()
            ->senderAddressType('alphanumeric')
            ->validityPeriod(300);

        self::assertNull($options->getRecipientId());
        self::assertSame([
            'clientMessageId' => 'client-id',
            'contentCategory' => 'informational',
            'maxSmsPerMessage' => 2,
            'messageType' => 'default',
            'notificationCallbackUrl' => 'https://example.com/delivery-report',
            'priority' => 5,
            'sendAsFlashSms' => true,
            'senderAddressType' => 'alphanumeric',
            'validityPeriode' => 300,
        ], $options->toArray());
    }

}
