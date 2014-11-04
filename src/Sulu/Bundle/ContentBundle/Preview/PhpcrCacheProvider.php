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

/**
 * provides a cache for preview with phpcr
 * @package SuluBundle\ContentBundle\Preview
 */
class PhpcrCacheProvider implements PreviewCacheProviderInterface
{
    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * prefix for property names and node name
     * @var string
     */
    private $prefix;

    public function __construct(
        ContentMapperInterface $contentMapper,
        SessionManagerInterface $sessionManager,
        $prefix = 'preview'
    ) {
        $this->contentMapper = $contentMapper;
        $this->sessionManager = $sessionManager;
        $this->prefix = $prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function contains($userId, $contentUuid, $webspaceKey, $locale)
    {
        $cacheNode = $this->getPreviewCacheNode($userId, $webspaceKey);

        return $cacheNode !== false &&
        $cacheNode->getPropertyValueWithDefault($this->getContentPropertyName(), '') === $contentUuid &&
        $cacheNode->getPropertyValueWithDefault($this->getLocalePropertyName(), '') === $locale;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($userId, $webspaceKey)
    {
        $this->removePreviewCacheNode($userId, $webspaceKey);

        $this->sessionManager->getSession()->save();
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($userId, $contentUuid, $webspaceKey, $locale)
    {
        $this->delete($userId, $webspaceKey);

        $session = $this->sessionManager->getSession();
        $src = $session->getNodeByIdentifier($contentUuid);
        $destParent = $this->sessionManager->getTempNode($webspaceKey, $userId);

        $session->save();

        $srcPath = $src->getPath();
        $destPath = $destParent->getPath() . '/' . $this->prefix;
        $session->getWorkspace()->copy($srcPath, $destPath);

        $session->refresh(true);

        $cacheNode = $this->getPreviewCacheNode($userId, $webspaceKey);
        $cacheNode->setProperty($this->getContentPropertyName(), $contentUuid);
        $cacheNode->setProperty($this->getLocalePropertyName(), $locale);
        $cacheNode->setProperty($this->getChangesPropertyName(), '{}');

        $session->save();

        return $this->fetchStructure($userId, $webspaceKey, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchStructure($userId, $webspaceKey, $locale)
    {
        $cacheNode = $this->getPreviewCacheNode($userId, $webspaceKey);

        if ($cacheNode === false) {
            return false;
        }

        $content = $this->contentMapper->load($cacheNode->getIdentifier(), $webspaceKey, $locale);
        $content->setUuid($cacheNode->getPropertyValue($this->getContentPropertyName()));

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function saveStructure(StructureInterface $content, $userId, $contentUuid, $webspaceKey, $locale)
    {
        $contentArray = $this->getContentArray($content);
        $cacheNode = $this->getPreviewCacheNode($userId, $webspaceKey);

        $this->contentMapper->setNoRenamingFlag(true);
        $result = $this->contentMapper->save(
            $contentArray,
            $content->getKey(),
            $webspaceKey,
            $locale,
            $userId,
            true,
            $cacheNode->getIdentifier()
        );
        $this->contentMapper->setNoRenamingFlag(false);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchChanges($userId, $webspaceKey, $remove = true)
    {
        $cacheNode = $this->getPreviewCacheNode($userId, $webspaceKey);
        if ($cacheNode === false) {
            return array();
        }

        $changes = $cacheNode->getPropertyValueWithDefault($this->getChangesPropertyName(), array());
        if ($remove) {
            $this->saveChanges(array(), $userId, $webspaceKey);
        }

        return json_decode($changes, true);
    }

    /**
     * {@inheritdoc}
     */
    public function saveChanges($changes, $userId, $webspaceKey)
    {
        $cacheNode = $this->getPreviewCacheNode($userId, $webspaceKey);
        $cacheNode->setProperty($this->getChangesPropertyName(), json_encode($changes));

        $this->sessionManager->getSession()->save();

        return $changes;
    }

    /**
     * {@inheritdoc}
     */
    public function updateTemplate($template, $userId, $contentUuid, $webspaceKey, $locale)
    {
        $cacheNode = $this->getPreviewCacheNode($userId, $webspaceKey);
        $cacheNode->setProperty('i18n:' . $locale . '-template', $template);

        $this->sessionManager->getSession()->save();
    }

    /**
     * {@inheritdoc}
     */
    public function appendChanges($changes, $userId, $webspaceKey)
    {
        $changes = array_merge($this->fetchChanges($userId, $webspaceKey), $changes);

        return $this->saveChanges($changes, $userId, $webspaceKey);
    }

    /**
     *
     */
    private function getContentArray(StructureInterface $content)
    {
        $contentArray = $content->toArray();

        // remove resourcelocators before save temporary content
        if ($content->hasTag('sulu.rlp')) {
            $rlpProperties = $content->getPropertiesByTagName('sulu.rlp');
            foreach ($rlpProperties as $property) {
                unset($contentArray[$property->getName()]);
            }
        }

        return $contentArray;
    }

    /**
     * returns node to cache data
     */
    private function getPreviewCacheNode($userId, $webspaceKey, $force = false)
    {
        $node = $this->sessionManager->getTempNode($webspaceKey, $userId);

        if ($node->hasNode($this->prefix)) {
            return $node->getNode($this->prefix);
        }

        if ($force) {
            $previewNode = $node->addNode($this->prefix);
            $previewNode->addMixin('mix:referenceable');

            $this->sessionManager->getSession()->save();

            return $previewNode;
        }

        return false;
    }

    /**
     * removes preview cache node if it exists
     */
    private function removePreviewCacheNode($userId, $webspaceKey)
    {
        $node = $this->getPreviewCacheNode($userId, $webspaceKey);
        if ($node !== false) {
            $node->remove();
        }
    }

    /**
     * returns prefixed property name
     */
    private function getPropertyName($postfix)
    {
        return $this->prefix . '-' . $postfix;
    }

    /**
     * returns property name for content
     */
    private function getContentPropertyName()
    {
        return $this->getPropertyName('content');
    }

    /**
     * returns property name for content
     */
    private function getChangesPropertyName()
    {
        return $this->getPropertyName('changes');
    }

    /**
     * returns property name for locale
     */
    private function getLocalePropertyName()
    {
        return $this->getPropertyName('locale');
    }
}
