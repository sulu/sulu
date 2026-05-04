<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Notifier\Tests\Application\Notifier;

use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Component\Notifier\Transport\TransportInterface;

final class RecordingTransportFactory extends AbstractTransportFactory
{
    public function __construct(private readonly RecordingTransport $transport)
    {
        parent::__construct();
    }

    public function create(Dsn $dsn): TransportInterface
    {
        return $this->transport;
    }

    protected function getSupportedSchemes(): array
    {
        return ['recording'];
    }
}
