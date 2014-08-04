<?php

namespace vendor\sulu\sulu\src\Sulu\Component\Content\Mapper;

class ContentWriter
{
    const STATE_PROPERTY_NAME = 'state';
    const PUBLISHED_PROPERTY_NAME = 'published';

    protected $sessionManager;

    public function write(
        $structure,
        $data,
        $languageCode,
        $userId,
        $partialUpdate = true,
        $uuid = null,
        $parentUuid = null,
        $state = null,
        $showInNavigation = null
    )
    {
        $contentRootNode = $this->getContentNode($webspaceKey);

        /** @var PHPCR\SessionInterface */
        $session = $this->sessionManager->getSession();

        if ($parentUuid !== null) {
            $root = $session->getNodeByIdentifier($parentUuid);
        } else {
            $root = $contentRootNode;
        }

        $suluProperty = $structure->getPropertyByTagName('sulu.node.name');
        $path = $this->cleaner->cleanUp($data[$suluProperty->getName()], $languageCode);

        $currentDateTime = new \DateTime();

        $newTranslatedNode = function (NodeInterface $node) use ($userId, $currentDateTime, &$state, &$showInNavigation) {
            $node->setTranslatedProperty('creator', $userId);
            $node->setTranslatedProperty('created', $currentDateTime);

            if (!isset($state)) {
                $state = StructureInterface::STATE_TEST;
            }
            if (!isset($showInNavigation)) {
                $showInNavigation = false;
            }
        };

        /** @var NodeInterface $node */
        if ($uuid === null) {
            // create a new node
            $path = $this->getUniquePath($path, $root);
            $node = $root->addNode($path);
            $newTranslatedNode($node);

            $node->addMixin('sulu:content');
        } else {
            $node = $session->getNodeByIdentifier($uuid);
            if (!$node->hasTranslatedProperty('template')) {

                $newTranslatedNode($node);
            } else {
                $hasSameLanguage = ($languageCode == $this->defaultLanguage);
                $hasSamePath = ($node->getPath() !== $this->getContentNode($webspaceKey)->getPath());
                $hasDifferentTitle = !$node->hasTranslatedProperty($suluProperty->getName()) ||
                    $node->getTranslatedPropertyValue(
                        $suluProperty->getName()
                    ) !== $data[$suluProperty->getName()];

                if ($hasSameLanguage && $hasSamePath && $hasDifferentTitle) {
                    $path = $this->getUniquePath($path, $node->getParent());
                    $node->rename($path);
                    // FIXME refresh session here
                }
            }
        }
        $node->setTranslatedProperty('template', $templateKey);
        $node->setTranslatedProperty('changer', $userId);
        $node->setTranslatedProperty('changed', $dateTime);

        if (isset($data['nodeType'])) {
            $node->setTranslatedProperty('nodeType', $data['nodeType']);
        }

        // do not state transition for root (contents) node
        if ($node->getPath() !== $contentRootNode->getPath() && isset($state)) {

            // dan: this should probably be atomic, i.e. on ecall for each state and published
            $this->changeState(
                $node,
                $state,
                $structure
            );
        }

        if (isset($showInNavigation)) {
            $node->setTranslatedProperty('navigation', $showInNavigation);
        }

        $postSave = array();

        // go through every property in the template
        /** @var PropertyInterface $property */
        foreach ($structure->getProperties(true) as $suluProperty) {

            // allow null values in data
            if (isset($data[$suluProperty->getName()])) {
                $type = $this->getContentType($property->getContentTypeName());

                // @todo: this should be getting the value from the PHPCR node not
                //        the original data.
                $value = $data[$suluProperty->getName()];
                $suluProperty->setValue($value);

                // add property to post save action
                if ($type->getType() == ContentTypeInterface::POST_SAVE) {
                    $postSave[] = array(
                        'type' => $type,
                        'property' => $suluProperty
                    );
                } else {
                    $type->write(
                        $node,

                        // @todo: this is for BC. I don't want to refactor all the content types just yet.
                        new TranslatedProperty($suluProperty, $languageCode, $this->languageNamespace),
                        $userId,
                        $webspaceKey,
                        $languageCode,
                        null
                    );
                }
            } elseif ($suluProperty->getMandatory()) {
                $type = $this->getContentType($suluProperty->getContentTypeName());
                $type->read($node, $suluProperty, $webspaceKey, $languageCode, null);

                if ($suluProperty->getValue() === $type->getDefaultValue()) {
                    throw new MandatoryPropertyException($templateKey, $suluProperty);
                }
            } elseif (!$partialUpdate) {
                $type = $this->getContentType($suluProperty->getContentTypeName());
                // if it is not a partial update remove property
                $type->remove(
                    $node,
                    new TranslatedProperty($suluProperty, $languageCode, $this->languageNamespace),
                    $webspaceKey,
                    $languageCode,
                    null
                );
            }
            // if it is a partial update ignore property
        }

        // save node now
        $session->save();

        // set post save content types properties
        foreach ($postSave as $post) {
            try {
                /** @var ContentTypeInterface $type */
                $type = $post['type'];
                /** @var PropertyInterface $property */
                $suluProperty = $post['property'];

                $type->write(
                    $node,

                    // @todo: Remove the TranslatedProperty - cannot do this now because we need BC
                    new TranslatedProperty($property, $languageCode, $this->languageNamespace),
                    $userId,
                    $webspaceKey,
                    $languageCode,
                    null
                );
            } catch (\Exception $ex) {
                // TODO Introduce a PostSaveException, so that we don't have to catch everything
                // FIXME message for user or log entry
                throw $ex;
            }
        }
        $session->save();

        // save data of extensions
        foreach ($structure->getExtensions() as $extension) {

            // @todo: Factor out languageNamespace and internalPrefix here
            $extension->setLanguageCode($languageCode, $this->languageNamespace, $this->internalPrefix);
            if (isset($data['extensions']) && isset($data['extensions'][$extension->getName()])) {
                $extension->save($node, $data['extensions'][$extension->getName()], $webspaceKey, $languageCode);
            } else {
                $extension->load($node, $webspaceKey, $languageCode);
            }
        }

        $session->save();
    }

    /**
     * change state of given node
     * @param NodeInterface $node node to change state
     * @param int $state new state
     * @param \Sulu\Component\Content\StructureInterface $structure
     * @param string self::STATE_PROPERTY_NAME
     * @param string self::PUBLISHED_PROPERTY_NAME
     *
     * @throws \Sulu\Component\Content\Exception\StateTransitionException
     * @throws \Sulu\Component\Content\Exception\StateNotFoundException
     */
    private function changeState(
        NodeInterface $node,
        $state,
        StructureInterface $structure
    ) {
        if (!in_array($state, $this->states)) {
            throw new StateNotFoundException($state);
        }

        // no state (new node) set state
        if (!$node->hasTranslatedProperty(self::STATE_PROPERTY_NAME)) {
            $node->setTranslatedProperty(self::STATE_PROPERTY_NAME, $state);
            $structure->setNodeState($state);

            // published => set only once
            if ($state === StructureInterface::STATE_PUBLISHED && !$node->hasProperty(self::PUBLISHED_PROPERTY_NAME)) {
                $node->setTranslatedProperty(self::PUBLISHED_PROPERTY_NAME, new DateTime());
            }

            return;
        }

        $oldState = $node->getTranslatedPropertyValue(self::STATE_PROPERTY_NAME);

        // old state is the same as new state
        if ($oldState === $state) {
            return;
        }
        
        // from test to published
        if (
            $oldState === StructureInterface::STATE_TEST &&
            $state === StructureInterface::STATE_PUBLISHED
        ) {
            $node->setTranslatedProperty(self::STATE_PROPERTY_NAME, $state);
            $structure->setNodeState($state);

            // set only once
            if (!$node->hasTranslatedProperty(self::PUBLISHED_PROPERTY_NAME)) {
                $node->setTranslatedProperty(self::PUBLISHED_PROPERTY_NAME, new DateTime());
            }

            return;
        }

        // from published to test
        if (
            $oldState === StructureInterface::STATE_PUBLISHED &&
            $state === StructureInterface::STATE_TEST
        ) {
            $node->setTranslatedProperty(self::STATE_PROPERTY_NAME, $state);
            $structure->setNodeState($state);

            // set published date to null
            $node->getTranslatedProperty(self::PUBLISHED_PROPERTY_NAME)->remove();

            return;
        }

        throw new \RuntimeException(sprintf(
            'Could not determine state transition from "%s" to "%s". This should never happen.',
            $oldState, $state
        ));
    }
}
