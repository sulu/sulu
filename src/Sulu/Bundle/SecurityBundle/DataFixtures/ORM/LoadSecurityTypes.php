<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\SecurityBundle\Entity\SecurityType;
use Symfony\Component\DependencyInjection\ContainerAware;

class LoadSecurityTypes extends ContainerAware implements FixtureInterface, OrderedFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        // force id = 1
        $metadata = $manager->getClassMetaData(get_class(new SecurityType()));
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $file = $this->container->getParameter('sulu_security.security_types.fixture');
        $doc = new \DOMDocument();
        $doc->load($file);

        $xpath = new \DOMXpath($doc);
        $elements = $xpath->query('/security-types/security-type');

        if (!is_null($elements)) {
            /** @var $element \DOMNode */
            foreach ($elements as $element) {
                $securityType = new SecurityType();
                $children = $element->childNodes;
                /** @var $child \DOMNode */
                foreach ($children as $child) {
                    if (isset($child->nodeName)) {
                        if ($child->nodeName == 'id') {
                            $securityType->setId($child->nodeValue);
                        }
                        if ($child->nodeName == 'name') {
                            $securityType->setName($child->nodeValue);
                        }
                    }
                }
                $manager->persist($securityType);
            }
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 5;
    }
}
