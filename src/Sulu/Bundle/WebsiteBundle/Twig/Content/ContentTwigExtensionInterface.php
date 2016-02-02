<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig\Content;

use Sulu\Bundle\WebsiteBundle\Twig\Exception\ParentNotFoundException;

/**
 * Provide Interface to load content.
 */
interface ContentTwigExtensionInterface extends \Twig_ExtensionInterface
{
    /**
     * Returns resolved content for parent of given uuid.
     *
     * @param string $uuid
     *
     * @throws ParentNotFoundException
     *
     * @return array
     */
    public function loadParent($uuid);

    /**
     * Returns resolved content for uuid.
     *
     * @param string $uuid
     *
     * @return array
     */
    public function load($uuid);

    /**
     * {@inheritdoc}
     */
    public function getFunctions();
}
