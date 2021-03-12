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
use Sulu\Bundle\EventLogBundle\Collector\DoctrineDomainEventCollectorInterface;
use Sulu\Bundle\TagBundle\Entity\TagRepository;
use Sulu\Bundle\TagBundle\Event\TagCreatedEvent;
use Sulu\Bundle\TagBundle\Event\TagDeleteEvent;
use Sulu\Bundle\TagBundle\Event\TagEvents;
use Sulu\Bundle\TagBundle\Event\TagMergedEvent;
use Sulu\Bundle\TagBundle\Event\TagMergeEvent;
use Sulu\Bundle\TagBundle\Event\TagModifiedEvent;
use Sulu\Bundle\TagBundle\Event\TagRemovedEvent;
use Sulu\Bundle\TagBundle\Tag\Exception\TagAlreadyExistsException;
use Sulu\Bundle\TagBundle\Tag\Exception\TagNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Responsible for centralized Tag Management.
 */
class TagManager implements TagManagerInterface
{
    /**
     * The repository for communication with the database.
     *
     * @var TagRepository
     */
    private $tagRepository;

    /**
     * @var ObjectManager
     */
    private $em;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var DoctrineDomainEventCollectorInterface
     */
    private $doctrineDomainEventCollector;

    public function __construct(
        TagRepositoryInterface $tagRepository,
        ObjectManager $em,
        EventDispatcherInterface $eventDispatcher,
        DoctrineDomainEventCollectorInterface $doctrineDomainEventCollector
    ) {
        $this->tagRepository = $tagRepository;
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
        $this->doctrineDomainEventCollector = $doctrineDomainEventCollector;
    }

    /**
     * Loads all the tags managed in this system.
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
     * @return TagInterface
     */
    public function findById($id)
    {
        return $this->tagRepository->findTagById($id);
    }

    /**
     * Loads the tag with the given name.
     *
     * @param $name
     *
     * @return TagInterface
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
                $this->doctrineDomainEventCollector->collect(new TagCreatedEvent($tag, $data));
            } else {
                $this->doctrineDomainEventCollector->collect(new TagModifiedEvent($tag, $data));

            }

            $this->em->flush();

            return $tag;
        } catch (UniqueConstraintViolationException $exc) {
            throw new TagAlreadyExistsException($name);
        }
    }

    /**
     * Deletes the given Tag.
     *
     * @param number $id The tag to delete
     *
     * @throws Exception\TagNotFoundException
     */
    public function delete($id)
    {
        $tag = $this->tagRepository->findTagById($id);

        if (!$tag) {
            throw new TagNotFoundException($id);
        }

        $this->em->remove($tag);
        $this->doctrineDomainEventCollector->collect(new TagRemovedEvent($tag->getId(), $tag->getName()));

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
     * @throws Exception\TagNotFoundException
     *
     * @return TagInterface The new Tag, which is valid for all given tags
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
            $this->doctrineDomainEventCollector->collect(
                new TagMergedEvent($srcTag->getId(), $srcTag->getName(), $destTag)
            );

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
     * @param $tagIds
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
     * @param $tagNames
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
