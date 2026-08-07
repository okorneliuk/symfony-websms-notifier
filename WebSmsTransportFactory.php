<?php

declare(strict_types=1);

/*
 * (c) Oleh Korneliuk <oleh.korneliuk@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Okorneliuk\Symfony\NotifierBridge\WebSms;

use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;

/**
 * @author Oleh Korneliuk <oleh.korneliuk@gmail.com>
 */
final class WebSmsTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): WebSmsTransport
    {
        $scheme = $dsn->getScheme();

        if ('websms' !== $scheme) {
            throw new UnsupportedSchemeException($dsn, 'websms', $this->getSupportedSchemes());
        }

        $uid = $this->getUser($dsn);
        $apiKey = $this->getPassword($dsn);
        $testMode = $dsn->getBooleanOption('test_mode', false);
        $host = 'default' === $dsn->getHost() ? null : $dsn->getHost();
        $port = $dsn->getPort();

        return (new WebSmsTransport($uid, $apiKey, $testMode, $this->client, $this->dispatcher))->setHost($host)->setPort($port);
    }

    protected function getSupportedSchemes(): array
    {
        return ['websms'];
    }
}
