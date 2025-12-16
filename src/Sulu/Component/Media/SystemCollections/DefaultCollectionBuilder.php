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

namespace Sulu\Component\Media\SystemCollections;

use Massive\Bundle\BuildBundle\Build\BuilderContext;
use Massive\Bundle\BuildBundle\Build\BuilderInterface;
use Sulu\Bundle\MediaBundle\Api\Collection;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

readonly class DefaultCollectionBuilder implements BuilderInterface
{
    private ?InputInterface $input;
    private ?OutputInterface $output;

    public function __construct(
        private CollectionManagerInterface $collectionManager,
        private WebspaceManagerInterface $webspaceManager,
    ) {
    }

    public function getName(): string
    {
        return 'default_collection';
    }

    public function getDependencies(): array
    {
        return ['database', 'fixtures'];
    }

    public function build(): void
    {
        foreach ($this->webspaceManager->getAllLocalizations() as $localization) {
            $locale = $localization->getLocale();

            $this->output->writeln('Updating test collection for ' . $localization);

            $existingId = $this->collectionManager->getByKey('test_collection', $locale)?->getId();
            if (null !== $existingId && $this->input->getOption('destroy')) {
                $this->collectionManager->delete($existingId);
                $existingId = null;
            }

            $this->collectionManager->save([
                'title' => 'Test Collection',
                'key' => 'test_collection',
                'type' => ['id' => 1], // Non System collection
                'locale' => $locale,
                'parent' => null,
                'id' => $existingId,
            ]);
        }
    }

    public function setContext(BuilderContext $context): void
    {
        $this->input = $context->getInput();
        $this->output = $context->getOutput();
    }
}
