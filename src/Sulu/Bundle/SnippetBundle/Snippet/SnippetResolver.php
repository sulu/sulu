<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Snippet;

use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolverInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;

/**
 * Resolves snippets by UUIDs.
 */
class SnippetResolver implements SnippetResolverInterface
{
    /**
     * @var array
     */
    private $snippetCache = [];

    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var StructureResolverInterface
     */
    private $structureResolver;

    public function __construct(
        ContentMapperInterface $contentMapper,
        StructureResolverInterface $structureResolver
    ) {
        $this->contentMapper = $contentMapper;
        $this->structureResolver = $structureResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($uuids, $webspaceKey, $locale, $shadowLocale = null)
    {
        $snippets = [];
        foreach ($uuids as $uuid) {
            if (!array_key_exists($uuid, $this->snippetCache)) {
                $snippet = $this->contentMapper->load($uuid, $webspaceKey, $locale);

                if (!$snippet->getHasTranslation() && $shadowLocale !== null) {
                    $snippet = $this->contentMapper->load($uuid, $webspaceKey, $shadowLocale);
                }

                $snippet->setIsShadow($shadowLocale !== null);
                $snippet->setShadowBaseLanguage($shadowLocale);

                $resolved = $this->structureResolver->resolve($snippet);
                $resolved['view']['template'] = $snippet->getKey();
                $resolved['view']['uuid'] = $snippet->getUuid();

                $this->snippetCache[$uuid] = $resolved;
            }

            $snippets[] = $this->snippetCache[$uuid];
        }

        return $snippets;
    }
}
