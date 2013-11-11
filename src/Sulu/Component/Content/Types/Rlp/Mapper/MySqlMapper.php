<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\Rlp\Mapper;


use Doctrine\Bundle\DoctrineBundle\Registry;
use PHPCR\NodeInterface;

class MySqlMapper extends RlpMapper {

    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @param Registry $doctrine
     */
    function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * creates a new route for given path
     * @param NodeInterface $contentNode reference node
     * @param string $path path to generate
     * @param string $portal key of portal
     * @return int|string id or uuid of new route
     */
    public function save(NodeInterface $contentNode, $path, $portal)
    {
        // TODO: Implement save() method.
    }

    /**
     * checks if given path is unique
     * @param string $path
     * @param string $portal key of portal
     * @return bool
     */
    public function unique($path, $portal)
    {
        // TODO: Implement unique() method.
    }
}
