<?php

namespace Sulu\Component\Content\Mapper;

class ContentLoader
{
    /**
     * @var LocalizationFinder
     */
    protected $requestedLocaleFinder;

    /**
     * @var ContentContext
     */
    protected $contentContext;

    public function __construct(
        LocalizationFinder $requestedLocaleFinder,
        ContentContext $contentContext
    )
    {
        $this->requestedLocaleFinder = $requestedLocaleFinder;
        $this->contentContext = $contentContext;
    }

    public function loadFromNode(
        SuluPhpcrNode $node,
        $webspaceKey,
        $options = array()
    )
    {
        $options = array_merge(array(
            'exclude_ghost_content' => true,
            'load_ghost_content' => false
        ), $options);

        $resolvedLocale = $requestedLocale;

        // if load ghost content, override requestedLocale
        if ($options['load_ghost_content']) {
            $resolvedLocale = $this->requestedLocaleFinder->getAvailableLocalization(
                $contentNode,
                $requestedLocale,
                $webspaceKey
            );
        }

        if ($options['exclude_ghost_content'] && $resolvedLocale != $requestedLocale) {
            return null;
        }

        $structure = $this->getStructureForNode($contentNode);

        // set structure to ghost, if the available requestedLocale does not match the requested one
        if ($resolvedLocale != $requestedLocale) {
            $structure->setType(StructureType::getGhost($resolvedLocale));
        }

        $this->refreshStructure($structure, $node);
    }

    public function getStructureForNode(SuluPhpcrNode $node)
    {
        $templateKey = $contentNode->getTranslatedPropertyValue(
            'template',
            $this->contentContext->getTemplateDefault()
        );

        $structure = $this->structureManager->getStructure($key);

        return $structure;
    }

    public function refreshStructure(
        StructureInterface $structure, 
        SuluPhpcrNode $contentNode, 
        $webspaceKey
    )
    {
        $structure->setHasTranslation($contentNode->hasTranslatedProperty('template'));
        $structure->setUuid($contentNode->getPropertyValue('jcr:uuid'));

        // @todo: Refactor this
        $structure->setPath(str_replace($this->getContentNode($webspaceKey)->getPath(), '', $contentNode->getPath()));
        $structure->setNodeType(
            $contentNode->getTranslatedPropertyValue('nodeType', Structure::NODE_TYPE_CONTENT)
        );

        $structure->setWebspaceKey($webspaceKey);
        $structure->setLanguageCode($node->getLocale());
        $structure->setCreator($contentNode->getTranslatedPropertyValue('creator', 0));
        $structure->setChanger($contentNode->getTranslatedPropertyValue('changer', 0));
        $structure->setCreated(
            $contentNode->getTranslatedPropertyValue('created', new \DateTime())
        );
        $structure->setChanged(
            $contentNode->getTranslatedPropertyValue('changed', new \DateTime())
        );
        $structure->setHasChildren($contentNode->hasNodes());
        $structure->setNodeState(
            $contentNode->getTranslatedPropertyValue(
                'state',
                StructureInterface::STATE_TEST
            )
        );
        $structure->setNavigation(
            $contentNode->getTranslatedPropertyValue('navigation', false)
        );
        $structure->setGlobalState(
            $this->getInheritedState($contentNode, 'state', $webspaceKey)
        );
        $structure->setPublished(
            $contentNode->getTranslatedPropertyValue('published', null)
        );

        // go through every property in the template
        /** @var PropertyInterface $property */
        foreach ($structure->getProperties(true) as $property) {
            if (!($property instanceof SectionPropertyInterface)) {
                $type = $this->getContentType($property->getContentTypeName());
                $type->read(
                    $contentNode,
                    new TranslatedProperty(
                        $property,
                        $resolvedLocale,
                        $this->contentContext->getLanguageNamespace()
                    ),
                    $webspaceKey,
                    $resolvedLocale,
                    null
                );
            }
        }

        // load data of extensions
        foreach ($structure->getExtensions() as $extension) {
            $extension->setLanguageCode($requestedLocale, $this->contentContext->getLanguageNamespace(), $this->contentContext->getPropertyPrefix());
            $extension->load($contentNode, $webspaceKey, $resolvedLocale);
        }

        // loads dependencies for internal links
        if ($structure->getNodeType() === Structure::NODE_TYPE_INTERNAL_LINK && $structure->hasTag('sulu.rlp')) {
            $internalUuid = $structure->getPropertyValueByTagName('sulu.rlp');

            if (!empty($internal)) {
                $structure->setInternalLinkContent(
                    $this->loadByUUid(
                        $internalUuid,
                        $webspaceKey,
                        $localization,
                        array(
                            'exclude_ghost_content' => false, 
                            'load_ghost_content' => $loadGhostContent
                        )
                    )
                );
            }
        }
    }

    /**
     * Returns the structure for a given UUID
     *
     * @param string $uuid UUID of the content
     * @param string $webspaceKey Key of webspace
     * @param string $languageCode Read data for given language
     * @param bool $loadGhostContent True if also a ghost page should be returned, otherwise false
     *
     * @return StructureInterface
     */
    private function loadByUUid($uuid, $webspaceKey, $languageCode, $options = array())
    {
        if ($this->stopwatch) {
            $this->stopwatch->start('contentManager.load');
        }
        $session = $this->getSession();
        $contentNode = $session->getNodeByIdentifier($uuid);

        $result = $this->loadFromNode($contentNode, $languageCode, $webspaceKey, $options);
        if ($this->stopwatch) {
            $this->stopwatch->stop('contentManager.load');
        }

        return $result;
    }
}
