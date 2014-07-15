<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Manager;

use Sulu\Component\Rest\ListBuilder\FieldDescriptor\DoctrineFieldDescriptor;

/**
 * Interface MediaFieldDescriptor
 * @package Sulu\Bundle\MediaBundle\Media\Manager
 */
interface MediaFieldDescriptorInterface
{
    /**
     * Return the FieldDescriptor by name
     * @param string $key
     * @return DoctrineFieldDescriptor
     */
    public function getFieldDescriptor($key);

    /**
     * Return the FieldDescriptors
     * @return $this
     */
    public function getFieldDescriptors();
} 
