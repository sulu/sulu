<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Bundle\MarkupBundle\Markup\MarkupParserInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\SimpleContentType;

/**
 * ContentType for TextEditor.
 */
class TextEditor extends SimpleContentType
{
    const INVALID_REGEX = '/(<%s:[a-z]+\b[^\/>]*)(\/>|>[^<]*<\/%s:[^\/>]*>)/';

    /**
     * @var string
     */
    private $template;

    /**
     * @var MarkupParserInterface
     */
    private $markupParser;

    /**
     * @var string
     */
    private $markupNamespace;

    public function __construct($template, MarkupParserInterface $markupParser, $markupNamespace = 'sulu')
    {
        parent::__construct('TextEditor', '');

        $this->template = $template;
        $this->markupParser = $markupParser;
        $this->markupNamespace = $markupNamespace;
    }

    /**
     * {@inheritdoc}
     */
    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $value = $node->getPropertyValueWithDefault($property->getName(), $this->defaultValue);
        $property->setValue($this->validate($value, $languageCode));

        return $value;
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
        if ($value !== null) {
            $node->setProperty($property->getName(),
                $this->removeValidation(
                    $this->removeIllegalCharacters($value)
                )
            );
        } else {
            $this->remove($node, $property, $webspaceKey, $languageCode, $segmentKey);
        }
    }

    /**
     * Returns validated content.
     *
     * @param string $content
     * @param string $locale
     *
     * @return string
     */
    private function validate($content, $locale)
    {
        $validation = $this->markupParser->validate($content, $locale);

        $regex = sprintf(self::INVALID_REGEX, $this->markupNamespace, $this->markupNamespace);
        foreach ($validation as $tag => $state) {
            if (false === strpos($tag, 'sulu:validation-state="' . $state . '"')) {
                $newTag = preg_replace($regex, '$1 sulu:validation-state="' . $state . '"$2', $tag);
                $content = str_replace($tag, $newTag, $content);
            }
        }

        return $content;
    }

    /**
     * Removes validation attributes.
     *
     * @param string $content
     *
     * @return string
     */
    private function removeValidation($content)
    {
        return preg_replace('/sulu:validation-state="[a-zA-Z ]*"/', '', $content);
    }

    /**
     * {@inheritdoc}
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
            'table' => new PropertyParameter('table', true),
            'link' => new PropertyParameter('link', true),
            'max_height' => new PropertyParameter('max_height', 300),
            'paste_from_word' => new PropertyParameter('paste_from_word', true),
        ];
    }
}
