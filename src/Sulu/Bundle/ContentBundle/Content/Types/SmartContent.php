<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content\Types;

use JMS\Serializer\Serializer;
use PHPCR\NodeInterface;
use Sulu\Bundle\ContentBundle\Content\SmartContentContainer;
use Sulu\Bundle\ContentBundle\Repository\NodeRepositoryInterface;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\PropertyInterface;
use Symfony\Component\HttpFoundation\Request;

use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * ContentType for TextEditor
 */
class SmartContent extends ComplexContentType
{
    /**
     * @var NodeRepositoryInterface
     */
    private $nodeRepository;

    /**
     * @var TagManagerInterface
     */
    private $tagManager;

    /**
     * @var string
     */
    private $template;

    /**
     * @var RequestStack
     */
    private $requestStack;

    function __construct(
        NodeRepositoryInterface $nodeRepository,
        TagManagerInterface $tagManager,
        RequestStack $requestStack,
        $template
    ) {
        $this->nodeRepository = $nodeRepository;
        $this->tagManager = $tagManager;
        $this->template = $template;
        $this->requestStack = $requestStack;
    }

    /**
     * returns a template to render a form
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * returns type of ContentType
     * PRE_SAVE or POST_SAVE
     * @return int
     */
    public function getType()
    {
        return self::PRE_SAVE;
    }

    /**
     * @param $data
     * @param PropertyInterface $property
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $segmentKey
     * @param bool $preview
     */
    protected function setData(
        $data,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey,
        $preview = false
    ) {
        $smartContent = new SmartContentContainer(
            $this->nodeRepository,
            $this->tagManager,
            $webspaceKey,
            $languageCode,
            $segmentKey,
            $preview
        );
        $smartContent->setConfig($data === null || !is_array($data) ? array() : $data);
        $property->setValue($smartContent);
    }

    /**
     * {@inheritdoc}
     */
    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $data = json_decode($node->getPropertyValueWithDefault($property->getName(), '{}'), true);

        if (!empty($data['tags'])) {
            $data['tags'] = $this->tagManager->resolveTagIds($data['tags']);
        }

        $this->setData($data, $property, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * {@inheritdoc}
     */
    public function readForPreview($data, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $this->setData($data, $property, $webspaceKey, $languageCode, $segmentKey, true);
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
        $value = $property->getValue();

        if (!empty($value['tags'])) {
            $value['tags'] = $this->tagManager->resolveTagNames($value['tags']);
        }

        // if whole smart-content container is pushed
        if (isset($value['config'])) {
            $value = $value['config'];
        }
        $node->setProperty($property->getName(), json_encode($value));
    }

    /**
     * remove property from given node
     * @param NodeInterface $node
     * @param PropertyInterface $property
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $segmentKey
     */
    public function remove(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        // TODO: Implement remove() method.
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultParams()
    {
        $params = parent::getDefaultParams();
        $params['max_per_page'] = 25;
        $params['page_parameter'] = '_page';

        return $params;
    }

    /**
     * {@inheritdoc}
     */
    public function getViewData(PropertyInterface $property)
    {
        $params = array_merge(
            $this->getDefaultParams(),
            $property->getParams()
        );

        $smartContent = $property->getValue();
        $data = (array) $smartContent->getData();
        $currentPage = $this->requestStack->getCurrentRequest()->get($params['page_parameter'], 1);

        $adapter = new ArrayAdapter($data);
        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage($params['max_per_page']);
        $pager->setCurrentPage($currentPage);

        return array(
            'pager' => $pager
        );
    }
}
