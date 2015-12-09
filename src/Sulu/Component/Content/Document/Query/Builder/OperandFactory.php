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

use Doctrine\ODM\PHPCR\Query\Builder\OperandDynamicFactory;
use Doctrine\ODM\PHPCR\Query\Builder\OperandFactory as BaseOperandFactory;

/**
 * This factory adds the `structrureField` method to the operand factory.
 */
class OperandFactory extends BaseOperandFactory
{
    /**
     * Evaluates to the value of the specified structure field.
     *
     * <code>
     * $qb->where()
     *   ->eq()
     *     ->structureField('document_alias.prop_name')
     *     ->literal('my_field_value')
     *   ->end()
     * ->end();
     * </code>
     *
     * @param string $field - name of field to check, including alias name.
     *
     * @factoryMethod OperandDynamicField
     *
     * @return OperandDynamicFactory
     */
    public function structureField($field)
    {
        return $this->addChild(new OperandDynamicStructureField($this, $field));
    }
}
