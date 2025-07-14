<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Twig;

use JMS\Serializer\SerializationContext;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Cache\MemoizeInterface;
use Sulu\Component\Serializer\ArraySerializerInterface;
use Sulu\Component\Tag\Request\TagRequestHandlerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TagTwigExtension extends AbstractExtension
{
    /**
     * @deprecated Requiring the TagManagerInterface instead of the TagRepository
     */
    public function __construct(
        private TagManagerInterface $tagManager,
        private TagRequestHandlerInterface $tagRequestHandler,
        private ArraySerializerInterface $serializer,
        private MemoizeInterface $memoizeCache,
    ) {
    }

    /**
     * @return array<TwigFunction>
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('sulu_tags', [$this, 'getTagsFunction']),
            new TwigFunction('sulu_tag_url', [$this, 'setTagUrlFunction']),
            new TwigFunction('sulu_tag_url_append', [$this, 'appendTagUrlFunction']),
            new TwigFunction('sulu_tag_url_clear', [$this, 'clearTagUrlFunction']),
        ];
    }

    /**
     * @return array
     */
    public function getTagsFunction()
    {
        return $this->memoizeCache->memoizeById(
            'sulu_tags',
            \func_get_args(),
            function() {
                $tags = $this->tagManager->findAll();

                $context = SerializationContext::create();
                $context->setSerializeNull(true);
                $context->setGroups(['partialTag']);

                return $this->serializer->serialize($tags, $context);
            }
        );
    }

    /**
     * Extends current URL with given tag.
     *
     * @param array $tag will be included in the URL
     * @param string $tagsParameter GET parameter name
     *
     * @return string
     */
    public function appendTagUrlFunction($tag, $tagsParameter = 'tags')
    {
        return $this->tagRequestHandler->appendTagToUrl($tag, $tagsParameter);
    }

    /**
     * Set tag to current URL.
     *
     * @param array $tag will be included in the URL
     * @param string $tagsParameter GET parameter name
     *
     * @return string
     */
    public function setTagUrlFunction($tag, $tagsParameter = 'tags')
    {
        return $this->tagRequestHandler->setTagToUrl($tag, $tagsParameter);
    }

    /**
     * Remove tag from current URL.
     *
     * @param string $tagsParameter GET parameter name
     *
     * @return string
     */
    public function clearTagUrlFunction($tagsParameter = 'tags')
    {
        return $this->tagRequestHandler->removeTagsFromUrl($tagsParameter);
    }
}
