<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
    public const INVALID_REGEX = '/(<%s-[a-z]+\b[^\/>]*)(\/>|>[^<]*<\/%s-[^\/>]*>)/';

    /**
     * @var MarkupParserInterface
     */
    private $markupParser;

    /**
     * @var string
     */
    private $markupNamespace;

    public function __construct(MarkupParserInterface $markupParser, $markupNamespace = 'sulu')
    {
        parent::__construct('TextEditor', '');

        $this->markupParser = $markupParser;
        $this->markupNamespace = $markupNamespace;
    }

    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $value = $node->getPropertyValueWithDefault($property->getName(), $this->defaultValue);
        $property->setValue(\is_string($value) ? $this->validate($value, $languageCode) : null);

        return $value;
    }

    public function write(
        NodeInterface $node,
        PropertyInterface $property,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        $value = $property->getValue();
        if (null !== $value) {
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

        $regex = \sprintf(self::INVALID_REGEX, $this->markupNamespace, $this->markupNamespace);
        foreach ($validation as $tag => $state) {
            if (false === \strpos($tag, 'sulu-validation-state="' . $state . '"')) {
                $newTag = \preg_replace($regex, '$1 sulu-validation-state="' . $state . '"$2', $tag);
                $content = \str_replace($tag, $newTag, $content);
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
        return \preg_replace('/ sulu-validation-state="[a-zA-Z ]*"/', '', $content);
    }

    public function getDefaultParams(?PropertyInterface $property = null)
    {
        return [
            'table' => new PropertyParameter('table', true),
            'link' => new PropertyParameter('link', true),
            'max_height' => new PropertyParameter('max_height', 300),
            'paste_from_word' => new PropertyParameter('paste_from_word', true),
        ];
    }
}
