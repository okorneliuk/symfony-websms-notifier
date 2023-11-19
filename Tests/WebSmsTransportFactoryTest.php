<?php

/*
 * (c) Oleh Korneliuk <oleh.korneliuk@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Okorneliuk\Symfony\NotifierBridge\WebSms\Tests;

use Okorneliuk\Symfony\NotifierBridge\WebSms\WebSmsTransportFactory;
use Symfony\Component\Notifier\Test\TransportFactoryTestCase;
use Symfony\Component\Notifier\Transport\TransportFactoryInterface;

final class WebSmsTransportFactoryTest extends TransportFactoryTestCase
{
    /**
     * @return WebSmsTransportFactory
     */
    public function createFactory(): TransportFactoryInterface
    {
        return new WebSmsTransportFactory();
    }

    public static function createProvider(): iterable
    {
        yield [
            'websms://host.test?from=acme',
            'websms://accountSid:authToken@host.test?from=acme&test_mode=0',
        ];

        yield [
            'websms://host.test?test_mode=1',
            'websms://uid:api_key@host.test?test_mode=1',
        ];
    }

    public static function supportsProvider(): iterable
    {
        yield [true, 'websms://uid:api_key@default'];
        yield [false, 'somethingElse://uid:api_key@default'];
    }

    public static function missingRequiredOptionProvider(): iterable
    {
        yield 'missing option: from' => ['somethingElse://uid:api_key@default'];
    }

    public static function unsupportedSchemeProvider(): iterable
    {
        yield ['somethingElse://uid@default'];
        yield ['somethingElse://:api_key@default'];
    }
}