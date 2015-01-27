<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\Form;

use Symfony\Component\Form\ResolvedFormTypeFactory as BaseResolvedFormTypeFactory;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use DTL\Component\Content\Form\ContentTypeInterface;
use DTL\Component\Content\Form\ContentResolvedType;

/**
 * This class extends the original factory and wraps Sulu ContentTypeInterfaces
 * instances in the ContentResolvedTypeInterface.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class ResolvedFormTypeFactory extends BaseResolvedFormTypeFactory
{
    /**
     * {@inheritdoc}
     */
    public function createResolvedType(FormTypeInterface $type, array $typeExtensions, ResolvedFormTypeInterface $parent = null)
    {
        if ($type instanceof ContentTypeInterface) {
            return new ContentResolvedType($type, $typeExtensions, $parent);
        }

        return parent::createResolvedType($type, $typeExtensions, $parent);
    }
}

