<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Tag\Request;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles tags in current request.
 */
class TagRequestHandler implements TagRequestHandlerInterface
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function getTags($tagsParameter = 'tags')
    {
        if ($this->requestStack->getCurrentRequest() !== null) {
            $tags = $this->requestStack->getCurrentRequest()->get($tagsParameter, '');
        } else {
            $tags = '';
        }

        return array_map(
            function ($item) {
                return trim($item);
            },
            array_filter(explode(',', $tags))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function appendTagToUrl($tag, $tagsParameter = 'tags')
    {
        $request = $this->requestStack->getCurrentRequest();

        if (is_array($tag) && !array_key_exists('name', $tag)) {
            return;
        }

        // extend comma separated list
        $tags = $request->get($tagsParameter, '');
        $tagsArray = array_filter(array_merge(explode(',', $tags), [$tag['name']]));
        $tags = implode(',', array_unique($tagsArray));

        // get all parameter and extend with new tags string
        $query = $request->query->all();
        $query = array_merge($query, [$tagsParameter => $tags]);

        $queryString = http_build_query($query);

        return $request->getPathInfo() . (strlen($queryString) > 0 ? '?' . $queryString : '');
    }

    /**
     * {@inheritdoc}
     */
    public function setTagToUrl($tag, $tagsParameter = 'tags')
    {
        $request = $this->requestStack->getCurrentRequest();

        if (is_array($tag) && !array_key_exists('name', $tag)) {
            return;
        }

        // get all parameter and extend with new tags string
        $query = $request->query->all();
        $query = array_merge($query, [$tagsParameter => $tag['name']]);

        $queryString = http_build_query($query);

        return $request->getPathInfo() . (strlen($queryString) > 0 ? '?' . $queryString : '');
    }

    /**
     * {@inheritdoc}
     */
    public function removeTagsFromUrl($tagsParameter = 'tags')
    {
        $request = $this->requestStack->getCurrentRequest();

        // get all parameter and extend with new tags string
        $query = $request->query->all();
        unset($query[$tagsParameter]);

        $queryString = http_build_query($query);

        return $request->getPathInfo() . (strlen($queryString) > 0 ? '?' . $queryString : '');
    }
}
