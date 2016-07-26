<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\ContactBundle\Entity\Country;

class LoadCountries implements FixtureInterface, OrderedFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        // get already stored countries
        $qb = $manager->createQueryBuilder();
        $qb->from(Country::class, 'c', 'c.code');
        $qb->select('c');
        $existingCountries = $qb->getQuery()->getResult();

        // load xml
        $file = dirname(__FILE__) . '/../countries.xml';
        $doc = new \DOMDocument();
        $doc->load($file);

        $xpath = new \DOMXpath($doc);
        $elements = $xpath->query('/Countries/Country');

        if (!is_null($elements)) {
            /** @var $element DOMNode */
            foreach ($elements as $element) {
                /** @var $child DOMNode */
                foreach ($element->childNodes as $child) {
                    if (isset($child->nodeName)) {
                        if ($child->nodeName == 'Name') {
                            $countryName = $child->nodeValue;
                        }
                        if ($child->nodeName == 'Code') {
                            $countryCode = $child->nodeValue;
                        }
                    }
                }

                $country = (array_key_exists($countryCode, $existingCountries)) ? $existingCountries[$countryCode] : new Country();
                $country->setName($countryName);
                $country->setCode($countryCode);
                $manager->persist($country);
            }
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }
}
