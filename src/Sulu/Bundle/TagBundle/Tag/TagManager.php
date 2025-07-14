<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tag;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ObjectManager;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\TagBundle\Domain\Event\TagCreatedEvent;
use Sulu\Bundle\TagBundle\Domain\Event\TagMergedEvent;
use Sulu\Bundle\TagBundle\Domain\Event\TagModifiedEvent;
use Sulu\Bundle\TagBundle\Domain\Event\TagRemovedEvent;
use Sulu\Bundle\TagBundle\Event\TagDeleteEvent;
use Sulu\Bundle\TagBundle\Event\TagEvents;
use Sulu\Bundle\TagBundle\Event\TagMergeEvent;
use Sulu\Bundle\TagBundle\Tag\Exception\TagAlreadyExistsException;
use Sulu\Bundle\TagBundle\Tag\Exception\TagNotFoundException;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Responsible for centralized Tag Management.
 */
class TagManager implements TagManagerInterface
{
    public function __construct(
        private TagRepositoryInterface $tagRepository,
        private ObjectManager $em,
        private EventDispatcherInterface $eventDispatcher,
        private DomainEventCollectorInterface $domainEventCollector,
        private ?TrashManagerInterface $trashManager = null
    ) {
    }

    /**
     * Loads all the tags managed in this system.
     *
     * @deprecated use the Repository findAll method
     *
     * @return TagInterface[]
     */
    public function findAll()
    {
        return $this->tagRepository->findAllTags();
    }

    /**
     * Loads the tag with the given id.
     *
     * @param $id number The id of the tag
     *
     * @return TagInterface|null
     */
    public function findById($id)
    {
        return $this->tagRepository->findTagById($id);
    }

    /**
     * Loads the tag with the given name.
     *
     * @return TagInterface|null
     */
    public function findByName($name)
    {
        return $this->tagRepository->findTagByName($name);
    }

    public function findOrCreateByName($name)
    {
        $tag = $this->findByName($name);

        if (!$tag) {
            $tag = $this->save(['name' => $name]);
        }

        return $tag;
    }

    public function save($data, $id = null)
    {
        $name = $data['name'];

        try {
            // load existing tag if id is given and create a new one otherwise
            if ($id) {
                $tag = $this->tagRepository->findTagById($id);
                if (!$tag) {
                    throw new TagNotFoundException($id);
                }
            } else {
                $tag = $this->tagRepository->createNew();
            }

            // update data
            $tag->setName($name);

            if (!$id) {
                $this->em->persist($tag);
                $this->domainEventCollector->collect(new TagCreatedEvent($tag, $data));
            } else {
                $this->domainEventCollector->collect(new TagModifiedEvent($tag, $data));
            }

            $this->em->flush();

            return $tag;
        } catch (UniqueConstraintViolationException $exc) {
            throw new TagAlreadyExistsException($name, $exc);
        }
    }

    /**
     * Deletes the given Tag.
     *
     * @param number $id The tag to delete
     *
     * @throws TagNotFoundException
     */
    public function delete($id)
    {
        $tag = $this->tagRepository->findTagById($id);

        if (!$tag) {
            throw new TagNotFoundException($id);
        }

        if (null !== $this->trashManager) {
            $this->trashManager->store(TagInterface::RESOURCE_KEY, $tag);
        }

        $this->em->remove($tag);
        $this->domainEventCollector->collect(new TagRemovedEvent($tag->getId(), $tag->getName()));

        $this->em->flush();

        // throw an tag.delete event
        $event = new TagDeleteEvent($tag);
        $this->eventDispatcher->dispatch($event, TagEvents::TAG_DELETE);
    }

    /**
     * Merges the source tag into the destination tag.
     * The source tag will be deleted.
     *
     * @param number $srcTagIds The source tags, which will be removed afterwards
     * @param number $destTagId The destination tag, which will replace the source tag
     *
     * @return TagInterface The new Tag, which is valid for all given tags
     *
     * @throws TagNotFoundException
     */
    public function merge($srcTagIds, $destTagId)
    {
        $srcTags = [];

        $destTag = $this->tagRepository->findTagById($destTagId);
        if (!$destTag) {
            throw new TagNotFoundException($destTagId);
        }

        foreach ($srcTagIds as $srcTagId) {
            $srcTag = $this->tagRepository->findTagById($srcTagId);

            if (!$srcTag) {
                throw new TagNotFoundException($srcTagId);
            }

            $this->em->remove($srcTag);

            $this->domainEventCollector->collect(new TagMergedEvent($destTag, $srcTag->getId(), $srcTag->getName()));
            $this->domainEventCollector->collect(new TagRemovedEvent($srcTag->getId(), $srcTag->getName(), [
                'wasMerged' => true,
                'destinationTagId' => $destTag->getId(),
            ]));

            $srcTags[] = $srcTag;
        }

        $this->em->flush();

        // throw an tag.merge event
        $event = new TagMergeEvent($srcTags, $destTag);
        $this->eventDispatcher->dispatch($event, TagEvents::TAG_MERGE);

        return $destTag;
    }

    /**
     * Resolves tag ids to names.
     *
     * @return array
     */
    public function resolveTagIds($tagIds)
    {
        $resolvedTags = [];

        foreach ($tagIds as $tagId) {
            $tag = $this->findById($tagId);
            if (null !== $tag) {
                $resolvedTags[] = $tag->getName();
            }
        }

        return $resolvedTags;
    }

    /**
     * Resolves tag names to ids.
     *
     * @return array
     */
    public function resolveTagNames($tagNames)
    {
        $resolvedTags = [];

        foreach ($tagNames as $tagName) {
            $tag = $this->findByName($tagName);
            if (null !== $tag) {
                $resolvedTags[] = $tag->getId();
            }
        }

        return $resolvedTags;
    }
}
