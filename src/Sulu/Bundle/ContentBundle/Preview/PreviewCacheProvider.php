<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Preview;

use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * CacheProvider for preview stores cached data in phpcr
 * @package Sulu\Bundle\ContentBundle\Preview
 */
class PreviewCacheProvider implements PreviewCacheProviderInterface
{
    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var SecurityContext
     */
    private $securityContext;

    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var string
     */
    private $prefix;

    function __construct($contentMapper, $securityContext, $sessionManager, $prefix = 'preview')
    {
        $this->contentMapper = $contentMapper;
        $this->prefix = $prefix;
        $this->securityContext = $securityContext;
        $this->sessionManager = $sessionManager;
    }

    /**
     * {@inheritdoc}
     */
    function fetch($id, $webspaceKey, $locale)
    {
        if ($this->contains($id, $webspaceKey, $locale)) {
            $tempNode = $this->getPreviewTempNode($webspaceKey);

            return $this->contentMapper->load($tempNode->getIdentifier(), $webspaceKey, $locale);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    function contains($id, $webspaceKey)
    {
        $tempNode = $this->getPreviewTempNode($webspaceKey);

        return ($tempNode !== null && $tempNode->getPropertyValue($this->prefix . '-page') === $id);
    }

    /**
     * {@inheritdoc}
     */
    function save($id, $data, $webspaceKey, $locale)
    {
        if ($data instanceof StructureInterface) {
            $this->removePreviewTempNode($webspaceKey);

            $tempNode = $this->getPreviewTempNode($webspaceKey);
            if ($tempNode === null) {
                $tempNode = $this->clonePreviewTempNodeFrom($id, $webspaceKey);
            }

            // remove resourcelocators before save temporary content
            $rlpProperties = $data->getPropertiesByTagName('sulu.rlp');
            $dataArray = $data->toArray();
            foreach ($rlpProperties as $property) {
                unset($dataArray[$property->getName()]);
            }

            $this->contentMapper->save(
                $dataArray,
                $data->getKey(),
                $webspaceKey,
                $locale,
                $this->getUserId(),
                true,
                $tempNode->getIdentifier()
            );

            $tempNode->setProperty($this->prefix . '-page', $id);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    function delete($id, $webspaceKey)
    {
        $this->removePreviewTempNode($webspaceKey);
    }

    /**
     * returns id of user
     * @return int
     */
    private function getUserId()
    {
        return $this->securityContext->getToken()->getUser()->getid();
    }

    /**
     * returns temporary node for current user
     * @param string $webspaceKey
     * @return \PHPCR\NodeInterface
     */
    private function getPreviewTempNode($webspaceKey)
    {
        $userTempNode = $this->sessionManager->getTempNode($webspaceKey, $this->getUserId());

        if (!$userTempNode->hasNode($this->prefix)) {
            return null;
        }

        return $userTempNode->getNode($this->prefix);
    }

    /**
     * removes preview temp node
     */
    private function removePreviewTempNode($webspaceKey)
    {
        $tempNode = $this->getPreviewTempNode($webspaceKey);

        if ($tempNode !== null) {
            $tempNode->remove();
            $this->sessionManager->getSession()->save();
        }
    }

    /**
     * clones given node to temporary
     */
    private function clonePreviewTempNodeFrom($id, $webspaceKey)
    {
        $nodePath = $this->sessionManager->getSession()->getNodeByIdentifier($id)->getPath();
        $tempNode = $this->sessionManager->getTempNode($webspaceKey, $this->getUserId());
        $tempPath = $tempNode->getPath() . '/' . $this->prefix;

        if ($this->sessionManager->getSession()->nodeExists($tempPath)) {
            $this->removePreviewTempNode($webspaceKey);
        }

        $this->sessionManager->getSession()->save();
        $this->sessionManager->getSession()->getWorkspace()->copy($nodePath, $tempPath);
        $this->sessionManager->getSession()->save();
        $this->sessionManager->getSession()->refresh(true);

        return $this->getPreviewTempNode($webspaceKey);
    }
}
