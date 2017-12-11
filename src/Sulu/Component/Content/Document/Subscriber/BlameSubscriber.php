<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use PHPCR\NodeInterface;
use Sulu\Component\Content\Document\Behavior\BlameBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedBlameBehavior;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RestoreEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Manages user blame (log who creator the document and who updated it last).
 */
class BlameSubscriber implements EventSubscriberInterface
{
    const CREATOR = 'creator';

    const CHANGER = 'changer';

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    public function __construct(PropertyEncoder $propertyEncoder)
    {
        $this->propertyEncoder = $propertyEncoder;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::HYDRATE => 'setBlamesOnDocument',
            Events::PERSIST => 'setBlamesOnNodeForPersist',
            Events::PUBLISH => 'setBlamesOnNodeForPublish',
            Events::RESTORE => ['setChangerForRestore', -32],
        ];
    }

    /**
     * Sets the changer and creator of the document.
     *
     * @param HydrateEvent $event
     */
    public function setBlamesOnDocument(HydrateEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        $node = $event->getNode();
        $locale = $event->getLocale();
        $encoding = $this->getPropertyEncoding($document);

        $accessor = $event->getAccessor();

        $accessor->set(
            static::CHANGER,
            $node->getPropertyValueWithDefault(
                $this->propertyEncoder->encode($encoding, static::CHANGER, $locale),
                null
            )
        );

        $accessor->set(
            static::CREATOR,
            $node->getPropertyValueWithDefault(
                $this->propertyEncoder->encode($encoding, static::CREATOR, $locale),
                null
            )
        );
    }

    /**
     * Sets the creator and changer for the persist event.
     *
     * @param PersistEvent $event
     */
    public function setBlamesOnNodeForPersist(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        $this->setBlamesOnNode(
            $document,
            $event->getNode(),
            $event->getLocale(),
            $event->getAccessor(),
            $event->getOption('user')
        );
    }

    /**
     * Sets the creator and changer for the publish event.
     *
     * @param PublishEvent $event
     */
    public function setBlamesOnNodeForPublish(PublishEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        $this->setBlamesOnNode(
            $document,
            $event->getNode(),
            $event->getLocale(),
            $event->getAccessor(),
            $document->getChanger()
        );
    }

    /**
     * Persists the data of creator and changer to the Node.
     *
     * @param LocalizedBlameBehavior $document
     * @param NodeInterface $node
     * @param string $locale string
     * @param DocumentAccessor $accessor
     * @param int $userId
     */
    public function setBlamesOnNode(
        LocalizedBlameBehavior $document,
        NodeInterface $node,
        $locale,
        DocumentAccessor $accessor,
        $userId
    ) {
        if (!$document instanceof BlameBehavior && !$locale) {
            return;
        }

        $encoding = $this->getPropertyEncoding($document);

        $creatorPropertyName = $this->propertyEncoder->encode($encoding, static::CREATOR, $locale);
        if (!$node->hasProperty($creatorPropertyName)) {
            $accessor->set(self::CREATOR, $userId);
            $node->setProperty(
                $creatorPropertyName,
                $document->getCreator()
            );
        }

        $accessor->set(self::CHANGER, $userId);
        $node->setProperty(
            $this->propertyEncoder->encode($encoding, static::CHANGER, $locale),
            $userId
        );
    }

    /**
     * Sets the changer for the restore event.
     *
     * @param RestoreEvent $event
     */
    public function setChangerForRestore(RestoreEvent $event)
    {
        $document = $event->getDocument();
        if (!$this->supports($event->getDocument())) {
            return;
        }

        $encoding = $this->getPropertyEncoding($document);

        $event->getNode()->setProperty(
            $this->propertyEncoder->encode($encoding, self::CHANGER, $event->getLocale()),
            $event->getOption('user')
        );
    }

    /**
     * Returns the encoding kind for the given document.
     *
     * @param $document
     *
     * @return string
     */
    private function getPropertyEncoding($document)
    {
        $encoding = 'system_localized';
        if ($document instanceof BlameBehavior) {
            $encoding = 'system';
        }

        return $encoding;
    }

    /**
     * Returns if the given document is supported by this subscriber.
     *
     * @param $document
     *
     * @return bool
     */
    private function supports($document)
    {
        return $document instanceof LocalizedBlameBehavior;
    }
}
