<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Content;

use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use PHPCR\Util\UUIDHelper;
use Sulu\Bundle\SnippetBundle\Snippet\DefaultSnippetManagerInterface;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetResolverInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\Structure\SnippetBridge;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\ContentTypeExportInterface;
use Sulu\Component\Content\ContentTypeInterface;

/**
 * ContentType for Snippets.
 */
class SnippetContent extends ComplexContentType implements ContentTypeExportInterface
{
    /**
     * @var SnippetResolverInterface
     */
    private $snippetResolver;

    /**
     * @var string
     */
    protected $template;

    /**
     * @var bool
     */
    protected $defaultEnabled;

    /**
     * @var DefaultSnippetManagerInterface
     */
    private $defaultSnippetManager;

    public function __construct(
        DefaultSnippetManagerInterface $defaultSnippetManager,
        SnippetResolverInterface $snippetResolver,
        $defaultEnabled,
        $template
    ) {
        $this->snippetResolver = $snippetResolver;
        $this->defaultSnippetManager = $defaultSnippetManager;
        $this->defaultEnabled = $defaultEnabled;
        $this->template = $template;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return ContentTypeInterface::PRE_SAVE;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * {@inheritdoc}
     */
    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $refs = [];
        if ($node->hasProperty($property->getName())) {
            $refs = $node->getProperty($property->getName())->getString();
        }

        $property->setValue($refs);
    }

    /**
     * {@inheritdoc}
     */
    public function write(
        NodeInterface $node,
        PropertyInterface $property,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        $values = $property->getValue();

        $snippetReferences = [];
        $values = is_array($values) ? $values : [];

        foreach ($values as $value) {
            if ($value instanceof SnippetBridge) {
                $snippetReferences[] = $value->getUuid();
            } elseif (is_array($value) && array_key_exists('uuid', $value) && UUIDHelper::isUUID($value['uuid'])) {
                $snippetReferences[] = $value['uuid'];
            } elseif (UUIDHelper::isUUID($value)) {
                $snippetReferences[] = $value;
            } else {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Property value must either be a UUID or a Snippet, "%s" given.',
                        gettype($value)
                    )
                );
            }
        }

        $node->setProperty($property->getName(), $snippetReferences, PropertyType::REFERENCE);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        if ($node->hasProperty($property->getName())) {
            $node->getProperty($property->getName())->remove();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultParams(PropertyInterface $property = null)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getViewData(PropertyInterface $property)
    {
        $viewData = [];
        foreach ($this->getSnippets($property) as $snippet) {
            $viewData[] = $snippet['view'];
        }

        return $viewData;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentData(PropertyInterface $property)
    {
        $contentData = [];
        foreach ($this->getSnippets($property) as $snippet) {
            $contentData[] = $snippet['content'];
        }

        return $contentData;
    }

    /**
     * Returns snippets with given property value.
     */
    private function getSnippets(PropertyInterface $property)
    {
        /** @var PageBridge $page */
        $page = $property->getStructure();
        $webspaceKey = $page->getWebspaceKey();
        $locale = $page->getLanguageCode();
        $shadowLocale = null;
        if ($page->getIsShadow()) {
            $shadowLocale = $page->getShadowBaseLanguage();
        }

        $refs = $property->getValue();
        $ids = $this->getUuids($refs);

        $snippetType = $this->getParameterValue($property->getParams(), 'snippetType');
        $default = $this->getParameterValue($property->getParams(), 'default', false);

        if (empty($ids) && $snippetType && $default && $this->defaultEnabled) {
            $ids = [
                $this->defaultSnippetManager->loadIdentifier($webspaceKey, $snippetType),
            ];

            // to filter null default snippet
            $ids = array_filter($ids);
        }

        return $this->snippetResolver->resolve($ids, $webspaceKey, $locale, $shadowLocale);
    }

    /**
     * {@inheritdoc}
     */
    public function getReferencedUuids(PropertyInterface $property)
    {
        $data = $property->getValue();

        return $this->getUuids($data);
    }

    /**
     * The data is not always normalized, so we normalize the data here.
     */
    private function getUuids($data)
    {
        return is_array($data) ? $data : [];
    }

    /**
     * Returns value of parameter.
     * If parameter not exists the default will be returned.
     *
     * @param PropertyParameter[] $parameter
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    private function getParameterValue(array $parameter, $name, $default = null)
    {
        if (!array_key_exists($name, $parameter)) {
            return $default;
        }

        return $parameter[$name]->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function exportData($propertyValue)
    {
        $uuids = $this->getUuids($propertyValue);

        if (empty($uuids)) {
            return '';
        }

        return json_encode($this->getUuids($propertyValue));
    }

    /**
     * {@inheritdoc}
     */
    public function importData(
        NodeInterface $node,
        PropertyInterface $property,
        $value,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey = null
    ) {
        $property->setValue(json_decode($value));
        $this->write($node, $property, $userId, $webspaceKey, $languageCode, $segmentKey);
    }
}
