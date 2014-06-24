<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ImageConverter\Command\Manager;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Command\CommandInterface;

/**
 * @package Sulu\Bundle\MediaBundle\Media\ImageConverter\Command\Manager
 */
interface ManagerInterface
{

    /**
     * @param string $name
     * @return CommandInterface
     */
    public function get($name);

} 
