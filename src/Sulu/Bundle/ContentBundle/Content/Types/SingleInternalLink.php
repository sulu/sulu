<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content\Types;

use PHPCR\NodeInterface;
use SebastianBergmann\Exporter\Exception;
use Sulu\Component\Content\PropertyInterface;
use Sulu\Component\Content\SimpleContentType;

/**
 * ContentType for SingleInternalLink
 */
class SingleInternalLink extends SimpleContentType
{
    private $template;

    public function __construct($template)
    {
        parent::__construct('SingleInternalLink', '');

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
        if ($node->getIdentifier() !== null && $value !== $node->getIdentifier()) {
            parent::write($node, $property, $userId, $webspaceKey, $languageCode, $segmentKey);
        } else {
            // FIXME validation and an own exception in sulu/sulu
            throw new \Exception();
        }
    }

    /**
     * returns a template to render a form
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }
}
