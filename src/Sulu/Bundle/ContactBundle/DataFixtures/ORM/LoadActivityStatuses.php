<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\ContactBundle\Entity\ActivityStatus;

class LoadActivityStatuses implements FixtureInterface, OrderedFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        // force id = 1
        $metadata = $manager->getClassMetaData(get_class(new ActivityStatus()));
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $file = dirname(__FILE__) . '/../activityStatuses.xml';
        $doc = new DOMDocument();
        $doc->load($file);

        $xpath = new DOMXpath($doc);
        $elements = $xpath->query('/ActivityStatuses/ActivityStatus');

        if (!is_null($elements)) {
            /** @var $element DOMNode */
            foreach ($elements as $element) {
                $status = new ActivityStatus();
                $children = $element->childNodes;
                /** @var $child DOMNode */
                foreach ($children as $child) {
                    if (isset($child->nodeName)) {
                        if ($child->nodeName == "Name") {
                            $status->setName($child->nodeValue);
                        }
                    }
                }
                $manager->persist($status);
            }
        }

        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 1;
    }

}
