<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Sulu\Bundle\SecurityBundle\Entity\SecurityType;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Load security-types from xml to database.
 *
 * @deprecated
 */
class LoadSecurityTypes implements FixtureInterface, OrderedFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function load(ObjectManager $manager)
    {
        // get already present
        $qb = $manager->createQueryBuilder();
        $qb->from(SecurityType::class, 's', 's.id');
        $qb->select('s');
        $present = $qb->getQuery()->getResult();

        // load xml
        $file = $this->container->getParameter('sulu_security.security_types.fixture');
        $doc = new \DOMDocument();
        $doc->load($file);

        $xpath = new \DOMXPath($doc);
        $elements = $xpath->query('/security-types/security-type');

        if (!\is_null($elements)) {
            /** @var \DOMNode $element */
            foreach ($elements as $element) {
                $typeId = null;
                $typeName = null;

                /** @var \DOMNode $child */
                foreach ($element->childNodes as $child) {
                    if (isset($child->nodeName)) {
                        if ('id' == $child->nodeName) {
                            $typeId = $child->nodeValue;
                        }
                        if ('name' == $child->nodeName) {
                            $typeName = $child->nodeValue;
                        }
                    }
                }

                if (!$typeId || !$typeName) {
                    continue;
                }

                $securityType = (\array_key_exists($typeId, $present)) ? $present[$typeId] : new SecurityType();
                $securityType->setId($typeId);
                $securityType->setName($typeName);
                $manager->persist($securityType);
            }
        }

        $manager->flush();
    }

    public function getOrder()
    {
        return 5;
    }
}
