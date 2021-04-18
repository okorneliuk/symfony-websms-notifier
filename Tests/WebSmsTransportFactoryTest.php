<?php

/*
 * (c) Oleh Korneliuk <oleh.korneliuk@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Notifier\Bridge\WebSms\Tests;

use Symfony\Component\Notifier\Bridge\WebSms\WebSmsTransportFactory;
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

    public function createProvider(): iterable
    {
        yield [
            'websms://host.test',
            'websms://uid:api_key@host.test?test_mode=0',
        ];

        yield [
            'websms://host.test?test_mode=1',
            'websms://uid:api_key@host.test?test_mode=1',
        ];
    }

    public function supportsProvider(): iterable
    {
        yield [true, 'websms://uid:api_key@default'];
        yield [false, 'somethingElse://uid:api_key@default'];
    }

    public function unsupportedSchemeProvider(): iterable
    {
        yield ['somethingElse://uid:api_key@default'];
    }

    public function incompleteDsnProvider(): iterable
    {
      yield ['websms://uid@default'];
      yield ['websms://:api_key@default'];
    }
}
