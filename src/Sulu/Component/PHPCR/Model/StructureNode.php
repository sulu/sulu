<?php

namespace Sulu\Component\PHPCR\Model;

use Sulu\Component\PhpcrDecorator\PHPCR\Node;

class StructureNode extends Node
{
    const PROPNAME_STATE = 'state';
    const PROPNAME_TEMPLATE = 'template';
    const PROPNAME_CREATOR = 'creator';
    const PROPNAME_CREATED = 'created';
    const PROPNAME_PUBLISHED = 'published';
    const PROPNAME_SHADOW_ON = 'shadow-on';
    const PROPNAME_SHADOW_BASE = 'shadow-base';
    const PROPNAME_NODE_TYPE = 'nodeType';

    private $node;
    private $decoratorFactory;
    private $structureManager;
    private $suluRequestContext;

    /**
     * Valid states
     * @var array
     */
    private $states = array(
        StructureInterface::STATE_PUBLISHED,
        StructureInterface::STATE_TEST
    );


    public function __construct(
        NodeInterface $node,
        DecoratorFactoryInterface $decoratorFactory,
        StructureManager $structureManager,
        SuluRequestContext $requestContext
    )
    {
        $this->node = $this;
        $this->decoratorFactory = $decoratorFactory;
        $this->structureManager = $structureManager;
        $this->suluRequestContext = $suluRequestContext;
    }

    public function getStructure()
    {
        $templateKey = $this->getLocalizedProperty(self::PROPNAME_TEMPLATE);
    }

    public function getLocalizedProperty($propertyName)
    {
        $propertyName = sprintf('%s:%s-%s',
            $this->localizationNamespace,
            $this->requestAnalyzer->getCurrentLocalization(),
            $propertyName
        );
    }

    public function getAvailableLocalizations()
    {
        $languages = array();
        foreach ($this->node->getProperties() as $property) {
            preg_match('/^' . $this->languageNamespace . ':(.*?)-' . self::PROPNAME_TEMPLATE . '/', $property->getName(), $matches);

            if ($matches) {
                $languages[$matches[1]] = $matches[1];
            }
        }

        return array_values($languages);
    }

    public function preSave()
    {
        if (!$this->hasLocalizedProperty(self::PROPNAME_CREATOR)) {
            $this->setLocalizedProperty(self::PROPNAME_CREATOR, $userId);
        }

        if (!$this->hasLocalizedProperty(self::PROPNAME_CREATED)) {
            $this->setLocalizedProperty(self::PROPNAME_CREATED, $dateTime);
        }

        if (!$this->hasLocalizedProperty(self::PROPNAME_STATE)) {
            $this->changeState('test');
        }
    }

    public function changeState($state)
    {
        if (!in_array($state, $this->states)) {
            throw new StateNotFoundException($state);
        }

        // no state (new this) set state
        if (null === $this->getState()) {
            $this->setState($state);

            // published => set only once
            if (!$this->getState() && $state === StructureInterface::STATE_PUBLISHED) {
                $this->setPublished(new DateTime());
            }

            return;
        }

        $oldState = $this->getState();

        // state has not changed
        if ($oldState === $state) {
            return;
        }

        // from test to published
        if (
            $oldState === StructureInterface::STATE_TEST &&
            $state === StructureInterface::STATE_PUBLISHED
        ) {
            $this->setProperty($statePropertyName, $state);
            $structure->setNodeState($state);

            // set only once
            if (!$this->getPublished()) {
                $this->setPublished(new \DateTime());
            }

            return;
        }
       
        // from published to test
        if (
            $oldState === StructureInterface::STATE_PUBLISHED &&
            $state === StructureInterface::STATE_TEST
        ) {
            $this->setState($state);

            // set published date to null
            $this->setPublished(null);
        }
    }

    private function setState($state)
    {
        $this->setLocalizedProperty(self::PROPNAME_STATE, $state);
    }

    public function getState()
    {
        return $this->getLocalizedPropertyValue(self::PROPNAME_STATE);
    }

    private function setPublished($date)
    {
        $this->setProperty(self::PROPNAME_PUBLISHED, new DateTime());
    }

    public function getPublished()
    {
        return $this->getLocalizedProperty(self::PROPNAME_PUBLISHED);
    }

    public function getTemplate()
    {
        return $this->getLocalizedProperty(self::PROPNAME_TEMPLATE);
    }

    public function getTitle()
    {
        $suluNameMetaProperty = $this->getStructure()->getPropertyByTagName('sulu.node.name');

        return $this->getLocalizedProperty($suluNameMetaProperty->getName());
    }

    public function getResourceLocator()
    {
        $resourceLocatorProperty = $this->getStructure()->getPropertyByTagName('sulu.rlp');
        return $resourceLocatorProperty->getValue();
    }

    /**
     * Validate and set the shadow language mapping.
     *
     * If a node of $language is set to shadow language $shadowBaseLanguage then
     * it must not shadow either itself or a language which is not concrete.
     *
     * @param string $shadowBaseLanguage
     * @throws \RuntimeException
     */
    public function setShadow($shadowBaseLocale)
    {
        if (null === $shadowBaseLocale) {
            $this->setTranslatedProperty(self::PROPNAME_SHADOW_ON, false);
            return;
        }

        $availableLocalizations = $this->getAvailableLocalizations();

        if ($this->getLocale() == $shadowBaseLocale) {
            throw new \RuntimeException(
                sprintf(
                    'Attempting to make language "%s" a shadow of itself! ("%s")',
                    $this->getLocale(),
                    $shadowBaseLocale
                )
            );
        }

        if (!in_array($shadowBaseLocale, $availableLocalizations)) {
            throw new \RuntimeException(
                sprintf(
                    'Attempting to make language "%s" a shadow of a non-concrete language "%s". Concrete languages are "%s"',
                    $this->getLocale(),
                    $shadowBaseLocale,
                    implode(', ', $availableLocalizations)
                )
            );
        }

        $this->setTranslatedProperty(self::PROPNAME_SHADOW_BASE, $shadowBaseLocaleOrNull); 
    }

    /**
     * Return the enabled shadow languages on the given node
     *
     * @param NodeInterface $node
     *
     * @return array
     */
    protected function getEnabledShadowLanguages()
    {
        // TODO..
        $nodeLanguages = $this->getAvailableLocalizations();
        $shadowBaseLanguages = array();

        foreach ($nodeLanguages as $nodeLanguage) {
            $propertyMap = clone $this->properties;
            $propertyMap->setLanguage($nodeLanguage);

            $shadowOn = $node->getPropertyValueWithDefault($propertyMap->getName('shadow-on'), null);

            if ($shadowOn) {
                $nodeShadowBaseLanguage = $node->getPropertyValueWithDefault(
                    $propertyMap->getName('shadow-base'),
                    null
                );

                if (null !== $nodeShadowBaseLanguage) {
                    $shadowBaseLanguages[$nodeShadowBaseLanguage] = $nodeLanguage;
                }
            }
        }

        return $shadowBaseLanguages;
    }

    /**
     * Return the "concrete" languages in a node - i.e. all languages
     * excluding shadow languages.
     *
     * @param NodeInterface $node
     *
     * @return array
     */
    protected function getConcreteLanguages(NodeInterface $node)
    {
        // TODO
        $enabledLanguages = $this->properties->getLanguagesForNode($node);
        $enabledShadowLanguages = $this->getEnabledShadowLanguages($node);
        $concreteTranslations = array_diff($enabledLanguages, array_values($enabledShadowLanguages));

        return $concreteTranslations;
    }

    public function setNodeType($nodeType)
    {
        $this->setLocalizedProperty(self::PROPNAME_NODE_TYPE, $nodeType);
    }

    public function getUuid()
    {
        return $this->getPropertyValue('jcr:uuid');
    }
}
