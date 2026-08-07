<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Tag\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Webmozart\Assert\Assert;

/**
 * Handles tags in current request.
 */
class TagRequestHandler implements TagRequestHandlerInterface
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function getTags($tagsParameter = 'tags')
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request instanceof Request) {
            $tags = $request->query->getString($tagsParameter);
        } else {
            $tags = '';
        }

        return \array_map(
            function($item) {
                return \trim($item);
            },
            \array_filter(\explode(',', $tags))
        );
    }

    public function appendTagToUrl($tag, $tagsParameter = 'tags')
    {
        $request = $this->requestStack->getCurrentRequest();
        Assert::notNull($request, 'The TagRequestHandler needs a request');

        if (\is_array($tag) && !\array_key_exists('name', $tag)) {
            return;
        }

        $tags = $request->query->getString($tagsParameter);
        /** @var array<string> $tagsArray */
        $tagsArray = \array_filter(\array_merge(\explode(',', $tags), [$tag['name']]));
        $tags = \implode(',', \array_unique($tagsArray));

        // get all parameter and extend with new tags string
        $query = $request->query->all();
        $query = \array_merge($query, [$tagsParameter => $tags]);

        $queryString = \http_build_query($query);

        return $request->getPathInfo() . (\strlen($queryString) > 0 ? '?' . $queryString : '');
    }

    public function setTagToUrl($tag, $tagsParameter = 'tags')
    {
        $request = $this->requestStack->getCurrentRequest();
        Assert::notNull($request, 'The TagRequestHandler needs a request');

        if (\is_array($tag) && !\array_key_exists('name', $tag)) {
            return;
        }

        // get all parameter and extend with new tags string
        $query = $request->query->all();
        $query = \array_merge($query, [$tagsParameter => $tag['name']]);

        $queryString = \http_build_query($query);

        return $request->getPathInfo() . (\strlen($queryString) > 0 ? '?' . $queryString : '');
    }

    public function removeTagsFromUrl($tagsParameter = 'tags')
    {
        $request = $this->requestStack->getCurrentRequest();
        Assert::notNull($request, 'The TagRequestHandler needs a request');

        // get all parameter and extend with new tags string
        $query = $request->query->all();
        unset($query[$tagsParameter]);

        $queryString = \http_build_query($query);

        return $request->getPathInfo() . (\strlen($queryString) > 0 ? '?' . $queryString : '');
    }
}
