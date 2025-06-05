<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\CacheLifetimeMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TemplateMetadata;
use Sulu\Bundle\AdminBundle\Metadata\XmlParserTrait;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeResolverInterface;

/**
 * @internal this class is not part of the public API and may be changed or removed without further notice
 */
class TemplateXmlParser
{
    use XmlParserTrait;

    public function load(\DOMXPath $xpath, \DOMNode $contextNode): TemplateMetadata
    {
        $templateMetadata = new TemplateMetadata();

        $controllerValue = $this->getValueFromXPath('x:controller', $xpath, $contextNode);

        if ($controllerValue) {
            \assert(\is_string($controllerValue), 'The <controller> value must be a "string", got "' . \get_debug_type($controllerValue) . '".');

            $templateMetadata->setController($controllerValue);
        }

        $viewValue = $this->getValueFromXPath('x:view', $xpath, $contextNode);

        if ($viewValue) {
            \assert(\is_string($viewValue), 'The <view> value must be a "string", got "' . \get_debug_type($viewValue) . '".');

            $templateMetadata->setView($viewValue);
        }

        $cacheLifeTimeNode = ($xpath->query('x:cacheLifetime', $contextNode) ?: null)?->item(0);

        if ($cacheLifeTimeNode) {
            $templateMetadata->setCacheLifetime($this->parseCacheLifeTime($cacheLifeTimeNode));
        }

        return $templateMetadata;
    }

    private function parseCacheLifeTime(\DOMNode $cacheLifeTimeNode): CacheLifetimeMetadata
    {
        $cacheLifeTimeValue = $cacheLifeTimeNode->nodeValue;
        $cacheLifeTimeType =
            $cacheLifeTimeNode->attributes?->getNamedItem('type')?->nodeValue
            ?? CacheLifetimeResolverInterface::TYPE_SECONDS;

        \assert(
            \in_array($cacheLifeTimeType, [
                CacheLifetimeResolverInterface::TYPE_SECONDS,
                CacheLifetimeResolverInterface::TYPE_EXPRESSION,
            ]),
            'The <cache_lifetime type="..."> must be one of the defined types (' . CacheLifetimeResolverInterface::TYPE_SECONDS . ', ' . CacheLifetimeResolverInterface::TYPE_EXPRESSION . '), got "' . $cacheLifeTimeType . '".',
        );

        return new CacheLifetimeMetadata($cacheLifeTimeType, $cacheLifeTimeValue);
    }
}
