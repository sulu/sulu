<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Search\EventSubscriber;

use Massive\Bundle\SearchBundle\Search\Event\HitEvent;
use Massive\Bundle\SearchBundle\Search\SearchEvents;
use Sulu\Bundle\SearchBundle\Search\Document;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AuthorSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            SearchEvents::HIT => 'handleAuthorHitMapping',
        ];
    }

    public function handleAuthorHitMapping(HitEvent $event): void
    {
        $document = $event->getHit()->getDocument();

        if (!$document instanceof Document) {
            return;
        }

        $document->setAuthored($this->getFieldValue($document, 'authored'));
    }

    /**
     * Return the named field from the document or return null.
     *
     * @param mixed $document
     * @param mixed $fieldName
     *
     * @return mixed
     */
    private function getFieldValue($document, $fieldName)
    {
        if (false === $document->hasField($fieldName)) {
            return null;
        }

        return $document->getField($fieldName)->getValue();
    }
}
