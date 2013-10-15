<?php
/**
 * Created by IntelliJ IDEA.
 * User: danielrotter
 * Date: 14.10.13
 * Time: 11:20
 * To change this template use File | Settings | File Templates.
 */

namespace Sulu\Bundle\ContentBundle\Mapper;


interface ContentMapperInterface
{
    /**
     * Saves the given data in the content storage
     * @param $data array Representation of the data to save
     */
    public function save($data);

    /**
     * Reads the data from the given path
     * @param $path
     * @return mixed
     */
    public function read($path);
}
