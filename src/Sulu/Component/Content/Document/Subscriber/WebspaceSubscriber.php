<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\EventDispatcher\Event;

class WebspaceSubscriber extends AbstractMappingSubscriber
{
    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    public function __construct(
        PropertyEncoder $encoder,
        DocumentInspector $inspector,
        WebspaceManagerInterface $webspaceManager
    ) {
        parent::__construct($encoder);

        $this->inspector = $inspector;
        $this->webspaceManager = $webspaceManager;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            // should happen after content is hydrated
            Events::HYDRATE => array('handleHydrate', -10),
            Events::PERSIST => array('handlePersist', 110),
        );
    }

    public function supports($document)
    {
        return $document instanceof WebspaceBehavior;
    }

    /**
     * @param AbstractMappingEvent|HydrateEvent $event
     * @throws \Sulu\Component\DocumentManager\Exception\DocumentManagerException
     */
    public function doHydrate(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();
        $webspaceName = $this->inspector->getWebspace($document);
        $event->getAccessor()->set('webspaceName', $webspaceName);

        if ($document instanceof StructureBehavior && $document->getStructureType() === null) {
            $this->setDefaultStructureType($event, $document, $webspaceName);
        }
    }

    /**
     * set the default structure type by webspace
     *
     * @param AbstractMappingEvent $event
     * @param mixed $document
     * @param string $webspaceName
     *
     * @throws \Sulu\Component\DocumentManager\Exception\DocumentManagerException
     */
    private function setDefaultStructureType(AbstractMappingEvent $event, $document, $webspaceName)
    {
        $structureMetadata = $this->inspector->getMetadata($document);

        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceName);
        $structureType = $webspace->getTheme()->getDefaultTemplate($structureMetadata->getAlias());

        if ($structureType !== null) {
            $event->getAccessor()->set('structureType', $structureType);
        }
    }

    /**
     * @param PersistEvent $event
     */
    public function doPersist(PersistEvent $event)
    {
        $this->doHydrate($event);
    }
}
