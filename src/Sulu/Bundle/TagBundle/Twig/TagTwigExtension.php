<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Twig;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Tag\Request\TagRequestHandlerInterface;

class TagTwigExtension extends \Twig_Extension
{
    /**
     * @var TagManagerInterface
     */
    private $tagManager;

    /**
     * @var TagRequestHandlerInterface
     */
    private $tagRequestHandler;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(
        TagManagerInterface $tagManager,
        TagRequestHandlerInterface $tagRequestHandler,
        SerializerInterface $serializer
    ) {
        $this->tagManager = $tagManager;
        $this->tagRequestHandler = $tagRequestHandler;
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('sulu_tags', [$this, 'getTagsFunction']),
            new \Twig_SimpleFunction('sulu_tag_url', [$this, 'setTagUrlFunction']),
            new \Twig_SimpleFunction('sulu_tag_url_append', [$this, 'appendTagUrlFunction']),
            new \Twig_SimpleFunction('sulu_tag_url_clear', [$this, 'clearTagUrlFunction']),
        ];
    }

    /**
     * @return array
     */
    public function getTagsFunction()
    {
        $tags = $this->tagManager->findAll();

        $context = SerializationContext::create();
        $context->setSerializeNull(true);
        $context->setGroups(['partialTag']);

        return $this->serializer->serialize($tags, 'array', $context);
    }

    /**
     * Extends current URL with given tag.
     *
     * @param array $tag will be included in the URL.
     * @param string $tagsParameter GET parameter name.
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
     * @param array $tag will be included in the URL.
     * @param string $tagsParameter GET parameter name.
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
     * @param string $tagsParameter GET parameter name.
     *
     * @return string
     */
    public function clearTagUrlFunction($tagsParameter = 'tags')
    {
        return $this->tagRequestHandler->removeTagsFromUrl($tagsParameter);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_tag';
    }
}
