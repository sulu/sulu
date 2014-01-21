<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tag;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\DBALException;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TagBundle\Entity\TagRepository;
use Sulu\Bundle\TagBundle\Event\TagDeleteEvent;
use Sulu\Bundle\TagBundle\Event\TagEvents;
use Sulu\Bundle\TagBundle\Event\TagMergeEvent;
use Sulu\Bundle\TagBundle\Tag\Exception\TagAlreadyExistsException;
use Sulu\Bundle\TagBundle\Tag\Exception\TagNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Responsible for centralized Tag Management
 * @package Sulu\Bundle\TagBundle\Tag
 */
class TagManager implements TagManagerInterface
{
    /**
     * The repository for communication with the database
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

    public function __construct(
        TagRepositoryInterface $tagRepository,
        ObjectManager $em,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->tagRepository = $tagRepository;
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Loads all the tags managed in this system
     * @return Tag[]
     */
    public function loadAll()
    {
        return $this->tagRepository->findAllTags();
    }

    /**
     * Loads the tag with the given id
     * @param $id number The id of the tag
     * @return Tag
     */
    public function loadById($id)
    {
        return $this->tagRepository->findTagById($id);
    }

    /**
     * Loads the tag with the given name
     * @param $name
     * @return Tag
     */
    public function loadByName($name)
    {
        // TODO: Implement loadByName() method.
    }

    /**
     * Saves the given Tag
     * @param array $data The data of the tag to save
     * @param number|null $id The id for saving the tag (optional)
     * @return Tag
     */
    public function save($data, $id = null)
    {
        $name = $data['name'];

        try {
            $tag = new Tag();
            $tag->setName($name);

            $tag->setCreated(new \DateTime());
            $tag->setChanged(new \DateTime());

            $this->em->persist($tag);
            $this->em->flush();

            return $tag;
        } catch (DBALException $exc) {
            if ($exc->getPrevious()->getCode() === '23000') { // Check if unique constraint fails
                throw new TagAlreadyExistsException($name);
            } else {
                throw $exc;
            }
        }
    }

    /**
     * Deletes the given Tag
     * @param number $id The tag to delete
     * @throws Exception\TagNotFoundException
     */
    public function delete($id)
    {
        $tag = $this->tagRepository->findTagById($id);

        if (!$tag) {
            throw new TagNotFoundException($id);
        }

        $this->em->remove($tag);
        $this->em->flush();

        // throw an tag.delete event
        $event = new TagDeleteEvent($tag);
        $this->eventDispatcher->dispatch(TagEvents::TAG_DELETE, $event);
    }

    /**
     * Merges the source tag into the destination tag.
     * The source tag will be deleted.
     * @param number $srcTagId The source tag, which will be removed afterwards
     * @param number $destTagId The destination tag, which will replace the source tag
     * @throws Exception\TagNotFoundException
     * @return Tag The new Tag, which is valid for both given tags
     */
    public function merge($srcTagId, $destTagId)
    {
        $srcTag = $this->tagRepository->findTagById($srcTagId);
        $destTag = $this->tagRepository->findTagById($destTagId);

        if (!$srcTag) {
            throw new TagNotFoundException($srcTagId);
        }

        if (!$destTag) {
            throw new TagNotFoundException($destTagId);
        }

        $this->em->remove($srcTag);
        $this->em->flush();

        // throw an tag.merge event
        $event = new TagMergeEvent($srcTag, $destTag);
        $this->eventDispatcher->dispatch(TagEvents::TAG_MERGE, $event);

        return $destTag;
    }
}
