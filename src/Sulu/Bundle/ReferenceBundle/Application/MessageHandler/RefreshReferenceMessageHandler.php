<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ReferenceBundle\Application\MessageHandler;

use Sulu\Bundle\ReferenceBundle\Application\Message\RefreshReferenceMessage;
use Sulu\Bundle\ReferenceBundle\Application\Refresh\ReferenceRefresherInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

class RefreshReferenceMessageHandler
{
    /**
     * @param array<ReferenceRefresherInterface> $referenceRefreshers
     */
    public function __construct(
        private ReferenceRepositoryInterface $referenceRepository,
        private iterable $referenceRefreshers,
        private string $suluContext,
    ) {
    }

    public function __invoke(RefreshReferenceMessage $message): void
    {
        $resourceKey = $message->getReferenceResourceKey();

        /** @var ReferenceRefresherInterface $referenceRefresher */
        $referenceRefresher = \iterator_to_array($this->referenceRefreshers)[$resourceKey] ?? null;
        if (!$referenceRefresher) {
            throw new \RuntimeException(\sprintf('No reference refresher found for resource key "%s". Available refreshers: %s',
                $resourceKey,
                \implode(', ', \array_keys($this->referenceRefreshers))
            ));
        }

        $counter = 0;
        $resourceIds = [];
        foreach ($referenceRefresher->refresh($message->getFilter()) as $object) {
            if (0 === (++$counter % 100)) {
                $this->referenceRepository->flush();
            }
            if ($object instanceof DimensionContentInterface) {
                $resourceIds[] = $object->getResource()->getId();
            }
        }

        $this->referenceRepository->flush();
        $this->referenceRepository->removeBy([
            'resourceIds' => $resourceIds,
        ]);
    }
}
