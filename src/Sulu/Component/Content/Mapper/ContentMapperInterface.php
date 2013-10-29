<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Mapper;


use Sulu\Component\Content\StructureInterface;

interface ContentMapperInterface
{
    /**
     * Saves the given data in the content storage
     * @param $data array The data to be saved
     * @param $language string Save data for given language
     * @param $templateKey string name of template
     * @return StructureInterface
     */
    public function save($data, $language, $templateKey = '');

    /**
     * Reads the data from the given path
     * @param $path string path to the content
     * @param $language string read data for given language
     * @return StructureInterface
     */
    public function read($path, $language);
}
