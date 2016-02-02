<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Preview;

use Sulu\Component\Content\Compat\Block\BlockPropertyInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * provides a mechanism to get a html value of a rdfa property.
 */
class RdfaCrawler
{
    /**
     * returns html value of rdfa property.
     *
     * @param string $html content to crawl
     * @param StructureInterface $content
     * @param string $property could be a property sequence like (block,1,title,0)
     *
     * @return bool
     */
    public function getPropertyValue($html, StructureInterface $content, $property)
    {
        // extract special property
        $crawler = new Crawler();
        $crawler->addHtmlContent($html, 'UTF-8');
        $nodes = $crawler;
        $before = '';
        if (false !== ($sequence = $this->getSequence($content, $property))) {
            foreach ($sequence['sequence'] as $item) {
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

    /**
     * extracts sequence information from property name.
     *
     * @param StructureInterface $content
     * @param string $property sequence like (block,1,title,0)
     *
     * @return array|bool
     */
    public function getSequence(StructureInterface $content, $property)
    {
        if (false !== strpos($property, ',')) {
            $sequence = explode(',', $property);
            $propertyPath = [];
            $indexSequence = [];
            $propertyInstance = $content->getProperty($sequence[0]);
            for ($i = 1; $i < count($sequence); ++$i) {
                // is not integer
                if (!ctype_digit(strval($sequence[$i]))) {
                    $propertyPath[] = $sequence[$i];
                    if ($propertyInstance instanceof BlockPropertyInterface) {
                        $lastIndex = $indexSequence[count($indexSequence) - 1];

                        unset($indexSequence[count($indexSequence) - 1]);
                        $indexSequence = array_values($indexSequence);

                        $propertyInstance = $propertyInstance->getProperties($lastIndex)->getProperty($sequence[$i]);
                    }
                } else {
                    $indexSequence[] = intval($sequence[$i]);
                }
            }

            return [
                'sequence' => $sequence,
                'propertyPath' => $propertyPath,
                'property' => $propertyInstance,
                'index' => $indexSequence,
            ];
        }

        return false;
    }
}
