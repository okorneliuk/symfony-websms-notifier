<?php

/*
 * (c) Oleh Korneliuk <oleh.korneliuk@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Notifier\Bridge\WebSms;

use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Component\Notifier\Transport\TransportInterface;

/**
 * @author Oleh Korneliuk <oleh.korneliuk@gmail.com>
 */
final class WebSmsTransportFactory extends AbstractTransportFactory
{
    /**
     * @return WebSmsTransport
     */
    public function create(Dsn $dsn): TransportInterface
    {
        $scheme = $dsn->getScheme();

        if ('websms' !== $scheme) {
            throw new UnsupportedSchemeException($dsn, 'websms', $this->getSupportedSchemes());
        }

        $uid = $this->getUser($dsn);
        $apiKey = $this->getPassword($dsn);
        $testMode = filter_var($dsn->getOption('test_mode', false), \FILTER_VALIDATE_BOOLEAN);
        $host = 'default' === $dsn->getHost() ? null : $dsn->getHost();
        $port = $dsn->getPort();

        return (new WebSmsTransport($uid, $apiKey, $testMode, $this->client, $this->dispatcher))->setHost($host)->setPort($port);
    }

    protected function getSupportedSchemes(): array
    {
        return ['websms'];
    }
}
