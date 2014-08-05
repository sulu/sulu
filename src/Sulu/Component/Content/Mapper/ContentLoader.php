<?php

namespace Sulu\Component\Content\Mapper;

use Sulu\Component\Content\StructureInterface;

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
        $requestedLocale,
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
                $phpcrNode,
                $requestedLocale,
                $webspaceKey
            );
        }

        if ($options['exclude_ghost_content'] && $resolvedLocale != $requestedLocale) {
            return null;
        }

        $structure = $this->getStructureForNode($phpcrNode);

        // set structure to ghost, if the available requestedLocale does not match the requested one
        if ($resolvedLocale != $requestedLocale) {
            $structure->setType(StructureType::getGhost($resolvedLocale));
        }

        $structure = $this->mapPhpcrNodeToStructure($node, $structure);

        return $structure;
    }

    public function getStructureForNode(SuluPhpcrNode $node)
    {
        $templateKey = $phpcrNode->getTranslatedPropertyValue(
            'template',
            $this->contentContext->getTemplateDefault()
        );

        $structure = $this->structureManager->getStructure($key);

        return $structure;
    }

    public function mapPhpcrNodeToStructure(
        SuluPhpcrNode $phpcrNode,
        StructureInterface $structure
    )
    {
        $webspaceKey = $this->contentContext->getWebspaceKey();

        // @todo: Refactor this: 
        $webspacePhpcrNode = $this->sessionManager->getContentNode($webspaceKey);
        $structure->setPath($webspacePhpcrNode->getPath(), '', $phpcrNode->getPath());

        $structure->setHasTranslation($phpcrNode->hasTranslatedProperty('template'));
        $structure->setUuid($phpcrNode->getPropertyValue('jcr:uuid'));
        $structure->setNodeType(
            $phpcrNode->getTranslatedPropertyValue('nodeType', Structure::NODE_TYPE_CONTENT)
        );

        $structure->setWebspaceKey($webspaceKey);
        $structure->setLanguageCode($node->getLocale());
        $structure->setCreator($phpcrNode->getTranslatedPropertyValue('creator', 0));
        $structure->setChanger($phpcrNode->getTranslatedPropertyValue('changer', 0));
        $structure->setCreated(
            $phpcrNode->getTranslatedPropertyValue('created', new \DateTime())
        );
        $structure->setChanged(
            $phpcrNode->getTranslatedPropertyValue('changed', new \DateTime())
        );
        $structure->setHasChildren($phpcrNode->hasNodes());
        $structure->setNodeState(
            $phpcrNode->getTranslatedPropertyValue(
                'state',
                StructureInterface::STATE_TEST
            )
        );
        $structure->setNavigation(
            $phpcrNode->getTranslatedPropertyValue('navigation', false)
        );
        $structure->setGlobalState(
            $this->getInheritedState($phpcrNode, 'state', $webspaceKey)
        );
        $structure->setPublished(
            $phpcrNode->getTranslatedPropertyValue('published', null)
        );

        // go through every property in the template
        /** @var PropertyInterface $property */
        foreach ($structure->getProperties(true) as $property) {
            if (!($property instanceof SectionPropertyInterface)) {
                $type = $this->getContentType($property->getContentTypeName());
                $type->read(
                    $phpcrNode,
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
            $extension->load($phpcrNode, $webspaceKey, $resolvedLocale);
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
    private function loadByUUid($uuid, $languageCode, $options = array())
    {
        if ($this->stopwatch) {
            $this->stopwatch->start('contentManager.load');
        }
        $session = $this->sessionManager->getSession();
        $phpcrNode = $session->getNodeByIdentifier($uuid);

        $result = $this->loadFromNode($phpcrNode, $languageCode, $options);
        if ($this->stopwatch) {
            $this->stopwatch->stop('contentManager.load');
        }

        return $result;
    }

    /**
     * calculates publich state of node
     */
    private function getInheritedState(SuluPhpcrNode $node, $statePropertyName)
    {
        $webspaceKey = $this->contentContext->getWebspaceKey();

        // @todo: $this->session->getWebspaceNodeByRole('content_index')
        $contentRootNode = $this->sessionManager->getContentNode($webspaceKey);

        // index page is default PUBLISHED
        if ($node->getName() === $contentRootNode->getPath()) {
            return StructureInterface::STATE_PUBLISHED;
        }

        // if test then return it
        $state = $node->getPropertyValueWithDefault($statePropertyName, StructureInterface::STATE_TEST);

        if ($state === StructureInterface::STATE_TEST) {
            return StructureInterface::STATE_TEST;
        }

        $session = $this->sessionManager->getSession();
        $workspace = $session->getWorkspace();
        $queryManager = $workspace->getQueryManager();

        $sql = 'SELECT *
                FROM  [sulu:content] as parent INNER JOIN [sulu:content] as child
                    ON ISDESCENDANTNODE(child, parent)
                WHERE child.[jcr:uuid]="' . $node->getIdentifier() . '"';

        $query = $queryManager->createQuery($sql, 'JCR-SQL2');
        $result = $query->execute();

        /** @var \PHPCR\NodeInterface $node */
        foreach ($result->getNodes() as $node) {
            // exclude /cmf/sulu_io/contents
            if (
                $node->getPath() !== $contentRootNode->getPath() &&
                $node->getPropertyValueWithDefault(
                    $statePropertyName,
                    StructureInterface::STATE_TEST
                ) === StructureInterface::STATE_TEST
            ) {
                return StructureInterface::STATE_TEST;
            }
        }

        return StructureInterface::STATE_PUBLISHED;
    }
}
