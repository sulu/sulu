<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * Provides a mechanism to get a html value of a rdfa property.
 */
class RdfaExtractor
{
    /**
     * @var Crawler
     */
    private $crawler;

    /**
     * @param string $html
     */
    public function __construct($html)
    {
        $this->crawler = new Crawler();
        $this->crawler->addHtmlContent($html, 'UTF-8');
    }

    /**
     * Returns html value for rdfa properties.
     *
     * @param string[] $properties Could be a property sequence like (block[1].title[0])
     *
     * @return array
     */
    public function getPropertyValues(array $properties)
    {
        $values = [];
        foreach ($properties as $property) {
            $values[$property] = $this->getPropertyValue($property);
        }

        return $values;
    }

    /**
     * Returns html and attributes value of rdfa property.
     *
     * @param string $property Could be a property sequence like (block[1].title[0])
     *
     * @return bool
     */
    public function getPropertyValue($property)
    {
        $nodes = $this->crawler;
        $before = '';
        $path = new PropertyPath($property);
        if (1 < $path->getLength()) {
            foreach ($path as $item) {
                // is not integer
                if (!ctype_digit(strval($item))) {
                    $before = $item;
                    $nodes = $nodes->filter('*[property="' . $item . '"]');
                } else {
                    $nodes = $nodes->filter('*[rel="' . $before . '"]')->eq($item);
                }
            }
        } else {
            // FIXME it is a bit complex but there is no :not operator in crawler
            // should be *[property="block"]:not(*[property] *)
            $nodes = $nodes->filter('*[property="' . $property . '"]')->reduce(
                function (Crawler $node) {
                    // get parents
                    $parents = $node->parents();
                    $count = 0;
                    // check if one parent is property exclude it
                    $parents->each(
                        function ($node) use (&$count) {
                            if (null !== $node->attr('property') && $node->attr('typeof') === 'collection') {
                                ++$count;
                            }
                        }
                    );

                    return $count === 0;
                }
            );
        }

        // if rdfa property not found return false
        if ($nodes->count() > 0) {
            // create an array of changes
            return $nodes->each(
                function (Crawler $crawlerNode) {
                    $node = $crawlerNode->getNode(0);
                    $attributes = [];
                    foreach ($node->attributes as $name => $value) {
                        $attributes[$name] = $value->nodeValue;
                    }
                    $attributes['html'] = $crawlerNode->html();

                    return $attributes;
                }
            );
        }

        return false;
    }
}
