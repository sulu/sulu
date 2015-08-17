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

use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class TagTwigExtension extends \Twig_Extension
{
    /**
     * @var TagManagerInterface
     */
    private $tagManager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(TagManagerInterface $tagManager, RequestStack $requestStack)
    {
        $this->tagManager = $tagManager;
        $this->requestStack = $requestStack;
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
     * @return Tag[]
     */
    public function getTagsFunction()
    {
        return $this->tagManager->findAll();
    }

    /**
     * Extends current URL with given tag.
     *
     * @param Tag $tag will be included in the URL.
     * @param string $tagsParameter GET parameter name.
     *
     * @return string
     */
    public function appendTagUrlFunction(Tag $tag, $tagsParameter = 'tags')
    {
        $request = $this->requestStack->getCurrentRequest();

        // extend comma separated list
        $tags = $request->get($tagsParameter, '');
        $tagsArray = array_filter(array_merge(explode(',', $tags), [$tag->getName()]));
        $tags = implode(',', array_unique($tagsArray));

        // get all parameter and extend with new tags string
        $query = $request->query->all();
        $query = array_merge($query, [$tagsParameter => $tags]);

        $queryString = http_build_query($query);

        return $request->getPathInfo() . (strlen($queryString) > 0 ? '?' . $queryString : '');
    }

    /**
     * Set tag to current URL.
     *
     * @param Tag $tag will be included in the URL.
     * @param string $tagsParameter GET parameter name.
     *
     * @return string
     */
    public function setTagUrlFunction(Tag $tag, $tagsParameter = 'tags')
    {
        $request = $this->requestStack->getCurrentRequest();

        // get all parameter and extend with new tags string
        $query = $request->query->all();
        $query = array_merge($query, [$tagsParameter => $tag->getName()]);

        $queryString = http_build_query($query);

        return $request->getPathInfo() . (strlen($queryString) > 0 ? '?' . $queryString : '');
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
        $request = $this->requestStack->getCurrentRequest();

        // get all parameter and extend with new tags string
        $query = $request->query->all();
        unset($query[$tagsParameter]);

        $queryString = http_build_query($query);

        return $request->getPathInfo() . (strlen($queryString) > 0 ? '?' . $queryString : '');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_tag';
    }
}
