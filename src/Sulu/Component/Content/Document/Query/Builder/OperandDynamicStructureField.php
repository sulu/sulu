<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\AbstractLeafNode;
use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode;

/**
 * Represents a field/property in a structure.
 */
class OperandDynamicStructureField extends AbstractLeafNode
{
    /**
     * @var string
     */
    protected $alias;

    /**
     * @var string
     */
    protected $structureField;

    /**
     * @param AbstractNode $parent
     * @param string $structureField
     */
    public function __construct(AbstractNode $parent, $structureField)
    {
        list($alias, $structureField) = $this->explodeField($structureField);
        $this->alias = $alias;
        $this->structureField = $structureField;
        parent::__construct($parent);
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeType()
    {
        return self::NT_OPERAND_DYNAMIC;
    }

    /** 
     * Return the document alias to which the structure that this field
     * represents belongs.
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Return the name of the structure field/property that this class
     * represents.
     *
     * @return string
     */
    public function getStructureField()
    {
        return $this->structureField;
    }
}
