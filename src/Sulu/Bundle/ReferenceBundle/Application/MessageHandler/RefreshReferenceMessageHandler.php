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
        private string $suluContext, // @phpstan-ignore-line - parameter for future use
    ) {
    }

    public function __invoke(RefreshReferenceMessage $message): void
    {
        $resourceKey = $message->getReferenceResourceKey();

        $refreshers = \iterator_to_array($this->referenceRefreshers);
        /** @var ReferenceRefresherInterface|null $referenceRefresher */
        $referenceRefresher = $refreshers[$resourceKey] ?? null;
        if (!$referenceRefresher) {
            // TODO add a logger warning here
            return;
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
            'resourceIds' => \array_map('strval', $resourceIds),
        ]);
    }
}
