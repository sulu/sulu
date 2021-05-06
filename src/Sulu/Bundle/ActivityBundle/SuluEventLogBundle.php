<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ActivityBundle;

use Sulu\Bundle\ActivityBundle\Domain\Model\EventRecordInterface;
use Sulu\Bundle\ActivityBundle\Infrastructure\Symfony\DependencyInjection\SuluEventLogExtension;
use Sulu\Bundle\PersistenceBundle\PersistenceBundleTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SuluEventLogBundle extends Bundle
{
    use PersistenceBundleTrait;

    public function build(ContainerBuilder $container): void
    {
        $this->buildPersistence(
            [
                EventRecordInterface::class => 'sulu.model.event_record.class',
            ],
            $container
        );
    }

    public function getContainerExtension()
    {
        return new SuluEventLogExtension();
    }
}
