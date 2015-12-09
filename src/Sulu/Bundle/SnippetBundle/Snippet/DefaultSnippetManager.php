<?php
/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Snippet;

use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\Webspace\Settings\SettingsManagerInterface;

/**
 * Manages default snippets.
 */
class DefaultSnippetManager implements DefaultSnippetManagerInterface
{
    /**
     * @var SettingsManagerInterface
     */
    private $settingsManager;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentRegistry
     */
    private $registry;

    public function __construct(
        SettingsManagerInterface $settingsManager,
        DocumentManagerInterface $documentManager,
        DocumentRegistry $registry
    ) {
        $this->settingsManager = $settingsManager;
        $this->documentManager = $documentManager;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function save($webspaceKey, $type, $uuid, $locale)
    {
        /** @var SnippetDocument $document */
        $document = $this->documentManager->find($uuid, $locale, ['rehydrate' => true]);

        if (!$document) {
            throw new SnippetNotFoundException($uuid);
        }

        if ($document->getStructureType() !== $type) {
            throw new WrongSnippetTypeException($type, $document);
        }

        $this->settingsManager->save(
            $webspaceKey,
            'snippets-' . $type,
            $this->registry->getNodeForDocument($document)
        );

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($webspaceKey, $type)
    {
        $this->settingsManager->remove($webspaceKey, 'snippets-' . $type);
    }

    /**
     * {@inheritdoc}
     */
    public function load($webspaceKey, $type, $locale)
    {
        $snippetNode = $this->settingsManager->load($webspaceKey, 'snippets-' . $type);

        if (null === $snippetNode) {
            return;
        }

        $uuid = $snippetNode->getIdentifier();
        /** @var SnippetDocument $document */
        $document = $this->documentManager->find($uuid, $locale, ['rehydrate' => true]);

        if (null !== $document && $document->getStructureType() !== $type) {
            throw new WrongSnippetTypeException($type, $document);
        }

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function loadIdentifier($webspaceKey, $type)
    {
        return $this->settingsManager->loadString($webspaceKey, 'snippets-' . $type);
    }
}
