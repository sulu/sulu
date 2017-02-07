<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Repository;

use Sulu\Component\Content\Repository\Mapping\MappingInterface;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Interface for content repository.
 */
interface ContentRepositoryInterface
{
    /**
     * Find content by uuid.
     *
     * @param string $uuid
     * @param string $locale
     * @param string $webspaceKey
     * @param MappingInterface $mapping Includes array of property names
     * @param UserInterface $user
     *
     * @return Content
     */
    public function find($uuid, $locale, $webspaceKey, MappingInterface $mapping, UserInterface $user = null);

    /**
     * Find content which are children of parent uuid.
     *
     * @param string $uuid
     * @param string $locale
     * @param string $webspaceKey
     * @param MappingInterface $mapping Includes array of property names
     * @param UserInterface $user
     *
     * @return Content[]
     */
    public function findByParentUuid(
        $uuid,
        $locale,
        $webspaceKey,
        MappingInterface $mapping,
        UserInterface $user = null
    );

    /**
     * Find content which are children of webspace root.
     *
     * @param string $locale
     * @param string $webspaceKey
     * @param MappingInterface $mapping Includes array of property names
     * @param UserInterface $user
     *
     * @return Content[]
     */
    public function findByWebspaceRoot($locale, $webspaceKey, MappingInterface $mapping, UserInterface $user = null);

    /**
     * Find content with uuid inclusive his parents and their siblings.
     *
     * @param string $locale
     * @param string $webspaceKey
     * @param MappingInterface $mapping Includes array of property names
     * @param UserInterface $user
     *
     * @return Content[]
     */
    public function findParentsWithSiblingsByUuid(
        $uuid,
        $locale,
        $webspaceKey,
        MappingInterface $mapping,
        UserInterface $user = null
    );

    /**
     * Find content array which given paths.
     *
     * @param string[] $paths
     * @param string $locale
     * @param MappingInterface $mapping Includes array of property names
     * @param UserInterface $user
     *
     * @return Content[]
     */
    public function findByPaths(
        array $paths,
        $locale,
        MappingInterface $mapping,
        UserInterface $user = null
    );

    /**
     * Find content array which given UUIDs.
     *
     * @param string[] $uuids
     * @param string $locale
     * @param MappingInterface $mapping Includes array of property names
     * @param UserInterface $user
     *
     * @return Content[]
     */
    public function findByUuids(
        array $uuids,
        $locale,
        MappingInterface $mapping,
        UserInterface $user = null
    );

    /**
     * Find all pages and returns an array of content.
     *
     * @param string $locale
     * @param string $webspaceKey
     * @param MappingInterface $mapping
     * @param UserInterface|null $user
     *
     * @return Content[]
     */
    public function findAll($locale, $webspaceKey, MappingInterface $mapping, UserInterface $user = null);

    /**
     * Find all pages and returns an array of content.
     *
     * @param string $locale
     * @param string $portalKey
     * @param MappingInterface $mapping
     * @param UserInterface|null $user
     *
     * @return Content[]
     */
    public function findAllByPortal($locale, $portalKey, MappingInterface $mapping, UserInterface $user = null);
}
