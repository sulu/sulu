<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\UserInterface\Command;

use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Sulu\Page\Application\Message\ApplyWorkflowTransitionPageMessage;
use Sulu\Page\Application\Message\CreatePageMessage;
use Sulu\Page\Application\MessageHandler\CreatePageMessageHandler;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal your code should not create direct dependencies on this implementation
 *           projects can utilize the `sulu:page:initialize` command to
 *           initialize new webspaces with the homepage
 *
 * @final
 */
#[AsCommand(name: 'sulu:page:initialize', description: 'Initializes the homepage per webspace locale')]
class InitializeHomepageCommand extends Command
{
    use HandleTrait;

    public function __construct(
        private WebspaceManagerInterface $webspaceManager,
        private PageRepositoryInterface $pageRepository,
        MessageBusInterface $messageBus
    ) {
        parent::__construct();
        $this->messageBus = $messageBus;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ui = new SymfonyStyle($input, $output);

        $webspaces = $this->webspaceManager->getWebspaceCollection();
        foreach ($webspaces as $webspace) {
            $localizations = $webspace->getLocalizations();
            foreach ($localizations as $localization) {
                if ($this->homepageExists($webspace, $localization->getLocale())) {
                    $ui->info(
                        \sprintf(
                            'Homepage for locale "%s" in webspace "%s" already exists',
                            $localization->getLocale(),
                            $webspace->getKey()
                        )
                    );
                    continue;
                }
                $homepage = $this->createHomepage($webspace, $localization->getLocale());
                $this->publishHomepage($homepage->getUuid(), $localization->getLocale());
            }
        }
        $ui->success('Homepage initialization completed');

        return Command::SUCCESS;
    }

    private function homepageExists(Webspace $webspace, string $locale): bool
    {
        $result = $this->pageRepository->countBy([
            'webspaceKey' => $webspace->getKey(),
            'locale' => $locale,
            'parentId' => null,
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ]);

        return $result > 0;
    }

    private function createHomepage(Webspace $webspace, string $locale): PageInterface
    {
        $webspaceKey = $webspace->getKey();
        $message = new CreatePageMessage(
            $webspaceKey,
            CreatePageMessageHandler::HOMEPAGE_PARENT_ID,
            [
                'title' => $webspace->getName(),
                'template' => $webspace->getDefaultTemplate('home'),
                'locale' => $locale,
                'url' => '/',
            ]
        );

        /** @see CreatePageMessageHandler */
        /** @var PageInterface $page */
        $page = $this->handle(new Envelope($message, [new EnableFlushStamp()]));

        return $page;
    }

    private function publishHomepage(string $uuid, string $locale): void
    {
        $message = new ApplyWorkflowTransitionPageMessage(
            identifier: ['uuid' => $uuid],
            locale: $locale,
            transitionName: WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH
        );

        /** @see ApplyWorkflowTransitionPageMessageHandler */
        $this->handle(new Envelope($message, [new EnableFlushStamp()]));
    }
}
