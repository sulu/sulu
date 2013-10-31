<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Controller;

use ReflectionMethod;
use Sulu\Bundle\ContentBundle\Controller\TemplateController;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\Types\ResourceLocator;
use Sulu\Component\Content\Types\TextArea;
use Sulu\Component\Content\Types\TextLine;
use Sulu\Component\PHPCR\SessionFactory\SessionFactoryService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @property SessionFactoryService sessionService
 * @property mixed structureMock
 */
class TemplateControllerTest extends WebTestCase
{
    public $structureFactoryMock;
    public $container;

    protected function setUp()
    {
        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testContentForm()
    {
    }

}
