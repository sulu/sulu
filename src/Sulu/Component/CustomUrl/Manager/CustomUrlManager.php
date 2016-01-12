<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Manager;

use Sulu\Component\CustomUrl\Document\CustomUrl;
use Sulu\Component\CustomUrl\Repository\CustomUrlRepository;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

/**
 * Manages custom-url documents and their routes.
 */
class CustomUrlManager
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var CustomUrlRepository
     */
    private $customUrlRepository;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    public function __construct(
        DocumentManagerInterface $documentManager,
        CustomUrlRepository $customUrlRepository,
        SessionManagerInterface $sessionManager
    ) {
        $this->documentManager = $documentManager;
        $this->customUrlRepository = $customUrlRepository;
        $this->sessionManager = $sessionManager;
    }

    public function create($webspaceKey, $data)
    {
        $document = new CustomUrl();
        $document->setTitle($data['title']);

        $this->documentManager->persist($document, null, ['path' => $this->getItemsPath($webspaceKey)]);

        return $document;
    }

    public function readList($webspaceKey)
    {
        // TODO pagination

        return $this->customUrlRepository->findList($this->getItemsPath($webspaceKey));
    }

    private function getItemsPath($webspaceKey)
    {
        return sprintf('%s/custom-urls/items', $this->sessionManager->getWebspacePath($webspaceKey));
    }
}
