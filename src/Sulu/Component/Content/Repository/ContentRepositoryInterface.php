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
     * @param string[] $mapping array of property names.
     * @param UserInterface $user
     *
     * @return Content
     */
    public function find($uuid, $locale, $webspaceKey, $mapping = [], UserInterface $user = null);

    /**
     * Find content which are children of parent uuid.
     *
     * @param string $uuid
     * @param string $locale
     * @param string $webspaceKey
     * @param string[] $mapping array of property names.
     * @param UserInterface $user
     *
     * @return Content[]
     */
    public function findByParentUuid($uuid, $locale, $webspaceKey, $mapping = [], UserInterface $user = null);

    /**
     * Find content which are children of webspace root.
     *
     * @param string $locale
     * @param string $webspaceKey
     * @param string[] $mapping array of property names.
     * @param UserInterface $user
     *
     * @return Content[]
     */
    public function findByWebspaceRoot($locale, $webspaceKey, $mapping = [], UserInterface $user = null);
}
