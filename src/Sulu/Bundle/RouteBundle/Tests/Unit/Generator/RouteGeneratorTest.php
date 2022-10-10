<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Tests\Unit\Generator;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\RouteBundle\Generator\RouteGenerator;
use Sulu\Bundle\RouteBundle\Generator\TokenProviderInterface;
use Sulu\Bundle\RouteBundle\Model\RoutableInterface;
use Symfony\Cmf\Api\Slugifier\SlugifierInterface;

class RouteGeneratorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<TokenProviderInterface>
     */
    private $tokenProvider;

    /**
     * @var ObjectProphecy<SlugifierInterface>
     */
    private $slugifier;

    /**
     * @var RouteGenerator
     */
    private $generator;

    public function setUp(): void
    {
        $this->tokenProvider = $this->prophesize(TokenProviderInterface::class);
        $this->slugifier = $this->prophesize(SlugifierInterface::class);

        $this->generator = new RouteGenerator($this->tokenProvider->reveal(), $this->slugifier->reveal());
    }

    public function testGenerate(): void
    {
        $entity = $this->prophesize(RoutableInterface::class);

        $this->tokenProvider->provide($entity->reveal(), 'object.getTitle()', Argument::any())->willReturn('Test Title');
        $this->tokenProvider->provide($entity->reveal(), 'object.getId()', Argument::any())->willReturn(1);

        $this->slugifier->slugify('Test Title')->willReturn('test-title');
        $this->slugifier->slugify(1)->willReturn('1');

        $path = $this->generator->generate(
            $entity->reveal(),
            ['route_schema' => '/prefix/{object.getTitle()}/postfix/{object.getId()}']
        );

        $this->assertEquals('/prefix/test-title/postfix/1', $path);
    }

    public function testGetOptionsResolver(): void
    {
        $optionsResolver = $this->generator->getOptionsResolver(['route_schema' => '/{entity.getTitle()}']);
        $this->assertEquals(['route_schema'], $optionsResolver->getRequiredOptions());
    }
}
