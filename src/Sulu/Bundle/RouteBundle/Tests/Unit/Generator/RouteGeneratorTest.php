<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Tests\Unit\Generator;

use Sulu\Bundle\RouteBundle\Generator\RouteGenerator;
use Sulu\Bundle\RouteBundle\Generator\TokenProviderInterface;
use Sulu\Bundle\RouteBundle\Model\RoutableInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Cmf\Api\Slugifier\SlugifierInterface;

class RouteGeneratorTest extends SuluTestCase
{
    /**
     * @var TokenProviderInterface
     */
    private $tokenProvider;

    /**
     * @var SlugifierInterface
     */
    private $slugifier;

    /**
     * @var RouteGenerator
     */
    private $generator;

    public function setUp()
    {
        $this->tokenProvider = $this->prophesize(TokenProviderInterface::class);
        $this->slugifier = $this->getContainer()->get('sulu_document_manager.slugifier');

        $this->generator = new RouteGenerator($this->tokenProvider->reveal(), $this->slugifier);
    }

    public function testGenerate()
    {
        $entity = $this->prophesize(RoutableInterface::class);

        $this->tokenProvider->provide($entity->reveal(), 'object.getTitle()')->willReturn('Test Title');
        $this->tokenProvider->provide($entity->reveal(), 'object.getId()')->willReturn(1);

        $path = $this->generator->generate(
            $entity->reveal(),
            ['route_schema' => '/prefix/{object.getTitle()}/postfix/{object.getId()}']
        );

        $this->assertEquals('/prefix/test-title/postfix/1', $path);
    }

    public function testGenerateLatinExtended()
    {
        $entity = $this->prophesize(RoutableInterface::class);

        $this->tokenProvider->provide($entity->reveal(), 'object.getTitle()')->willReturn('Tytuł testowy');
        $this->tokenProvider->provide($entity->reveal(), 'object.getId()')->willReturn(1);

        $path = $this->generator->generate(
            $entity->reveal(),
            ['route_schema' => '/prefix/{object.getTitle()}/postfix/{object.getId()}']
        );

        $this->assertEquals('/prefix/tytul-testowy/postfix/1', $path);
    }

    public function testGenerateNonLatin()
    {
        $entity = $this->prophesize(RoutableInterface::class);

        $this->tokenProvider->provide($entity->reveal(), 'object.getTitle()')->willReturn('Тестовий Заголовок ґ є і ї');
        $this->tokenProvider->provide($entity->reveal(), 'object.getId()')->willReturn(1);

        $path = $this->generator->generate(
            $entity->reveal(),
            ['route_schema' => '/prefix/{object.getTitle()}/postfix/{object.getId()}']
        );

        $this->assertEquals('/prefix/testovii-zagolovok-g-ie-i-yi/postfix/1', $path);
    }

    public function testGetOptionsResolver()
    {
        $optionsResolver = $this->generator->getOptionsResolver(['route_schema' => '/{entity.getTitle()}']);
        $this->assertEquals(['route_schema'], $optionsResolver->getRequiredOptions());
    }
}
