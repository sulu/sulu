<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Command;

use function GuzzleHttp\default_ca_bundle;
use Sulu\Bundle\ContentBundle\Document\BasePageDocument;
use Sulu\Bundle\ContentBundle\Document\HomeDocument;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Copies a given webspace with given locale to a destination webspace with a destination locale.
 */
class WebspaceCopyCommand extends ContainerAwareCommand
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;


    protected function configure()
    {
        $this->setName('sulu:webspaces:copy')
            ->addArgument('source-webspace', InputArgument::REQUIRED)
            ->addArgument('source-locale', InputArgument::REQUIRED)
            ->addArgument('destination-webspace', InputArgument::REQUIRED)
            ->addArgument('destination-locale', InputArgument::REQUIRED)
            ->setDescription('Copy whole webspace');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $webspaceKeySource = $input->getArgument('source-webspace');
        $localesSource = explode(',', $input->getArgument('source-locale'));
        $webspaceKeyDestination = $input->getArgument('destination-webspace');
        $localesDestination = explode(',', $input->getArgument('destination-locale'));

        if (count($localesSource) !== count($localesDestination)) {
            $output->writeln(
                '<info>Processing aborted: </info>' .
                '<comment>Provide correct source and destination locales</comment>'
            );

            return;
        }

        $output->writeln([
            '<info>Webspace Copy</info>',
            '<info>==============================</info>',
            '',
            '<info>Options</info>',
            '------------------------------',
            'Webspace: ' . $webspaceKeySource . ' => ' . $webspaceKeyDestination,
        ]);

        $localesPairs = [];
        for ($i = 0; $i < count($localesSource); $i++) {
            $localesPairs[] = $localesSource[$i] . ' => ' . $localesDestination[$i];
        }
        $output->writeln([
            'Locales: ' . join(', ' ,$localesPairs),
            '---------------',
            '',
        ]);

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('<question>Continue with this options?(y/n)</question> ', false);

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<error>Abort!</error>');

            return;
        }

        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');

        $this->output = $output;

        $this->output->writeln([
            '==============================',
            '1. Clear destination webspace',
        ]);
        $this->clearWebspace($webspaceKeyDestination);
        $this->output->writeln([
            '------------------------------',
            '',
        ]);

        $this->output->writeln([
            '2. Copy pages to destination webspace',
        ]);
        for ($i = 0; $i < count($localesSource); $i++) {
            $this->copyWebspace($webspaceKeySource, $localesSource[$i], $webspaceKeyDestination, $localesDestination[$i]);
        }

        $this->output->writeln([
            '3. Generate redirects',
        ]);
        for ($i = 0; $i < count($localesSource); $i++) {
            $this->generateRedirects($webspaceKeySource, $localesSource[$i], $webspaceKeyDestination, $localesDestination[$i]);
        }

        $this->output->writeln('<info>Done</info>');
    }

    /**
     * Removes all pages from given webspace.
     *
     * @param String $webspaceKey
     */
    protected function clearWebspace($webspaceKey)
    {
        $homeDocument = $this->documentManager->find($this->sessionManager->getContentPath($webspaceKey));
        foreach ($homeDocument->getChildren() as $child) {
            $this->documentManager->remove($child);
            $this->output->writeln('<info>Processing: </info>' . $child->getPath());
        }
        $this->documentManager->flush();
    }

    /**
     * Copies a given webspace with given locale to a destination webspace with a destination locale.
     *
     * @param string $webspaceKeySource
     * @param string $localeSource
     * @param string $webspaceKeyDestination
     * @param string $localeDestination
     */
    protected function copyWebspace($webspaceKeySource, $localeSource, $webspaceKeyDestination, $localeDestination) {
        $this->output->writeln([
            '------------------------------',
            '<info>Webspace: </info>' . $webspaceKeySource . ' => ' . $webspaceKeyDestination,
            '<info>Locale: </info>' . $localeSource . ' => ' . $localeDestination,
            '------------------------------',
        ]);

        /** @var HomeDocument $homeDocumentSource */
        $homeDocumentSource = $this->documentManager->find(
            $this->sessionManager->getContentPath($webspaceKeySource),
            $localeSource
        );

        // Generate all needed page documents.
        $this->recursiveCopy(
            $homeDocumentSource,
            null,
            $webspaceKeySource,
            $webspaceKeyDestination,
            $localeDestination
        );

        $this->output->writeln([
            '------------------------------',
            '',
        ]);
    }

    /**
     * Copies a given webspace with given locale to a destination webspace with a destination locale.
     *
     * @param string $webspaceKeySource
     * @param string $localeSource
     * @param string $webspaceKeyDestination
     * @param string $localeDestination
     */
    protected function generateRedirects($webspaceKeySource, $localeSource, $webspaceKeyDestination, $localeDestination) {
        $this->output->writeln([
            '------------------------------',
            '<info>Webspace: </info>' . $webspaceKeySource . ' => ' . $webspaceKeyDestination,
            '<info>Locale: </info>' . $localeSource . ' => ' . $localeDestination,
            '------------------------------',
        ]);

        /** @var HomeDocument $homeDocumentSource */
        $homeDocumentSource = $this->documentManager->find(
            $this->sessionManager->getContentPath($webspaceKeySource),
            $localeSource
        );

        // Generate links.
        $this->recursiveSetRedirects(
            $homeDocumentSource,
            $webspaceKeySource,
            $webspaceKeyDestination,
            $localeDestination
        );

        $this->output->writeln([
            '------------------------------',
            '',
        ]);
    }

    /**
     * @param BasePageDocument $documentSource
     * @param BasePageDocument|null $parentDocumentDestination
     * @param string $webspaceKeySource
     * @param string $webspaceKeyDestination
     * @param string $localeDestination
     */
    protected function recursiveCopy(
        BasePageDocument $documentSource,
        BasePageDocument $parentDocumentDestination = null,
        $webspaceKeySource,
        $webspaceKeyDestination,
        $localeDestination
    ) {
        // Generate new path.
        $newPath = str_replace(
            $this->sessionManager->getContentPath($webspaceKeySource),
            $this->sessionManager->getContentPath($webspaceKeyDestination),
            $documentSource->getPath()
        );

        $this->output->writeln('<info>Processing: </info>' . $documentSource->getPath() . ' => ' . $newPath);

        try {
            $documentDestination = $this->documentManager->find($newPath, $localeDestination);
        } catch (\Exception $exception) {
            $documentDestination = $this->documentManager->create('page');
        }

        // Set data.
        $documentDestination->setTitle($documentSource->getTitle());
        $documentDestination->setStructureType($documentSource->getStructureType());
        $documentDestination->setWorkflowStage(WorkflowStage::TEST);
        $documentDestination->getStructure()->bind($documentSource->getStructure()->toArray());
        $documentDestination->setExtensionsData($documentSource->getExtensionsData());
        $documentDestination->setResourceSegment($documentSource->getResourceSegment());
        // Set parent.
        if ($documentDestination instanceof PageDocument) {
            $documentDestination->setParent($parentDocumentDestination);
        }

        $this->saveDocument($documentDestination, $localeDestination);

        foreach ($documentSource->getChildren() as $child) {
            $this->recursiveCopy($child, $documentDestination, $webspaceKeySource, $webspaceKeyDestination, $localeDestination);
        }
    }

    /**
     * @param BasePageDocument $documentSource
     * @param string $webspaceKeySource
     * @param string $webspaceKeyDestination
     * @param string $localeDestination
     */
    protected function recursiveSetRedirects(
        BasePageDocument $documentSource,
        $webspaceKeySource,
        $webspaceKeyDestination,
        $localeDestination
    ) {
        // Generate new path.
        $newPath = str_replace(
            $this->sessionManager->getContentPath($webspaceKeySource),
            $this->sessionManager->getContentPath($webspaceKeyDestination),
            $documentSource->getPath()
        );

        $this->output->writeln('<info>Processing: </info>' . $documentSource->getPath() . ' => ' . $newPath);

        /** @var PageDocument $documentDestination */
        $documentDestination = $this->documentManager->find($newPath, $localeDestination);

        // Redirects.
        switch ($documentSource->getRedirectType()) {
            case RedirectType::INTERNAL:
                // Generate new target path.
                $newPathTarget = str_replace(
                    $this->sessionManager->getContentPath($webspaceKeySource),
                    $this->sessionManager->getContentPath($webspaceKeyDestination),
                    $documentSource->getRedirectTarget()->getPath()
                );

                $documentDestination->setRedirectType(RedirectType::INTERNAL);
                $documentDestination->setRedirectTarget($this->documentManager->find($newPathTarget, $localeDestination));

                $this->saveDocument($documentDestination, $localeDestination);
                break;
            case RedirectType::EXTERNAL:
                $documentDestination->setRedirectType(RedirectType::EXTERNAL);
                $documentDestination->setRedirectExternal($documentDestination->getRedirectExternal());

                $this->saveDocument($documentDestination, $localeDestination);
                break;
            default:
                break;
        }

        foreach ($documentSource->getChildren() as $child) {
            $this->recursiveSetRedirects($child, $webspaceKeySource, $webspaceKeyDestination, $localeDestination);
        }
    }

    /**
     * @param BasePageDocument $document
     * @param string $locale
     */
    protected function saveDocument(BasePageDocument $document, $locale)
    {
        $this->documentManager->persist($document, $locale);
        $this->documentManager->flush();
    }
}
