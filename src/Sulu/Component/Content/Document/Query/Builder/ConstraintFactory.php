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

use Doctrine\ODM\PHPCR\Query\Builder\ConstraintFactory as BaseConstraintFactory;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

/**
 * @see Doctrine\ODM\PHPCR\Query\Builder\ConstraintFactory
 */
class ConstraintFactory extends BaseConstraintFactory
{
    /**
     * {@inheritdoc}
     */
    public function eq()
    {
        return $this->addChild(new ConstraintComparison(
            $this, QOMConstants::JCR_OPERATOR_EQUAL_TO
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function neq()
    {
        return $this->addChild(new ConstraintComparison(
            $this, QOMConstants::JCR_OPERATOR_NOT_EQUAL_TO
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function lt()
    {
        return $this->addChild(new ConstraintComparison(
            $this, QOMConstants::JCR_OPERATOR_LESS_THAN
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function lte()
    {
        return $this->addChild(new ConstraintComparison(
            $this, QOMConstants::JCR_OPERATOR_LESS_THAN_OR_EQUAL_TO
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function gt()
    {
        return $this->addChild(new ConstraintComparison(
            $this, QOMConstants::JCR_OPERATOR_GREATER_THAN
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function gte()
    {
        return $this->addChild(new ConstraintComparison(
            $this, QOMConstants::JCR_OPERATOR_GREATER_THAN_OR_EQUAL_TO
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function like()
    {
        return $this->addChild(new ConstraintComparison(
            $this, QOMConstants::JCR_OPERATOR_LIKE
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function andX()
    {
        return $this->addChild(new ConstraintAndX($this));
    }

    /**
     * {@inheritdoc}
     */
    public function not()
    {
        return $this->addChild(new ConstraintNot($this));
    }

    /**
     * {@inheritdoc}
     */
    public function orX()
    {
        return $this->addChild(new ConstraintOrX($this));
    }
}
