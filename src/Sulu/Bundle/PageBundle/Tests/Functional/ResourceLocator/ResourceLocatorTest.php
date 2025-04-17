<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Functional\ResourceLocator;

use PHPCR\SessionInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Types\ResourceLocator;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

class ResourceLocatorTest extends SuluTestCase
{
    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var ResourceLocator
     */
    private $resourceLocator;

    protected function setUp(): void
    {
        $this->purgeDatabase();
        $this->initOrm();
        $this->initPhpcr();

        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $this->session = $this->sessionManager->getSession();

        $this->resourceLocator = new ResourceLocator('not-in-use');
    }

    protected function initOrm()
    {
    }

    public function testWrite(): void
    {
        $property = new Property('url', [], 'resource_locator');
        $property->setValue('/test');

        $node = $this->sessionManager->getContentNode('sulu_io')->addNode('test');
        $node->addMixin('sulu:content');
        $this->session->save();

        $this->resourceLocator->write($node, $property, 1, 'sulu_io', 'en', null);

        $this->assertEquals('/test', $node->getPropertyValue('url'));
    }

    public function testLoadFromProperty(): void
    {
        $property = new Property('url', [], 'resource_locator');

        $node = $this->sessionManager->getContentNode('sulu_io')->addNode('test');
        $node->addMixin('sulu:content');
        $node->setProperty($property->getName(), '/test');
        $this->session->save();

        $this->resourceLocator->read($node, $property, 1, 'sulu_io', 'en');

        $this->assertEquals('/test', $property->getValue());
    }

    public function testLoadFromNode(): void
    {
        $property = new Property('url', [], 'resource_locator');
        $property->setValue('/test');

        $node = $this->sessionManager->getContentNode('sulu_io')->addNode('test');
        $node->addMixin('sulu:content');
        $this->session->save();

        $this->resourceLocator->write($node, $property, 1, 'sulu_io', 'en', null);
        $this->session->save();

        $property->setValue('not-good');

        $this->resourceLocator->read($node, $property, 'sulu_io', 'en', null);

        $this->assertEquals('/test', $property->getValue());
        $this->assertEquals('/test', $node->getPropertyValue('url'));
    }
}
