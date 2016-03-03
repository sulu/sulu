<?php

namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\DocumentManagerRegistryInterface;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;

class WorkspaceSyncSubscriber implements EventSubscriberInterface
{
    /**
     * @var DocumentManagerRegistryInterface
     */
    private $registry;

    /**
     * @var string
     */
    private $liveWorkspaceName;

    /**
     * @var array
     */
    private $queue = array();

    public function __construct(DocumentManagerRegistryInterface $registry, $liveWorkspaceName)
    {
        $this->registry = $registry;
        $this->liveWorkspaceName = $liveWorkspaceName;
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => 'handlePersist',
            Events::FLUSH => 'handleFlush'
        ];
    }

    public function handlePersist(PersistEvent $event)
    {
        $this->queue[] = $event->getDocument();
    }

    public function handleFlush(FlushEvent $event)
    {
        $context = $event->getContext();
        $defaultManager = $context->getDocumentManager();
        $liveManager = $this->registry->getManager($this->liveWorkspaceName);

        // do not do anything if the default manager and live manager are the same.
        if ($defaultManager === $liveManager) {
            $this->queue = array();
            return;
        }

        $queue = $this->queue;
        while ($document = array_shift($queue)) {
            if ($document instanceof ResourceSegmentBehavior) {
                $document->setResourceSegment('/' . uniqid());
            }

            $path = $event->getContext()->getInspector()->getPath($document);

            $liveManager->persist(
                $document,
                'de',
                [
                    'path' => $path,
                    'auto_create' => true
                ]
            );
        }

        $liveManager->flush();
    }
}
