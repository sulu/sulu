<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\SimpleContentType;

/**
 * ContentType for Checkbox.
 */
class Checkbox extends SimpleContentType
{
    /**
     * form template for content type.
     *
     * @var string
     */
    private $template;

    public function __construct($template)
    {
        parent::__construct('Checkbox', false);

        $this->template = $template;
    }

    /**
     * {@inheritdoc}
     */
    public function write(
        NodeInterface $node,
        PropertyInterface $property,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        $value = $property->getValue();

        if ($value !== null && $value !== false && $value !== 'false' && $value !== '') {
            $node->setProperty($property->getName(), true);
        } else {
            $node->setProperty($property->getName(), false);
        }
    }

    /**
     * returns a template to render a form.
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultParams(PropertyInterface $property = null)
    {
        return [
            'type' => 'checkbox',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function importData(
        NodeInterface $node,
        PropertyInterface $property,
        $value,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey = null
    ) {
        $preparedValue = true;

        if ($value === '0') {
            $preparedValue = false;
        }

        parent::importData($node, $property, $preparedValue, $userId, $webspaceKey, $languageCode, $segmentKey);
    }
}
