<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Tests\Unit\Infrastructure\Sulu\Security;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Infrastructure\Sulu\Security\ArticleSecurityContextResolver;
use Sulu\Article\Infrastructure\Sulu\Security\ArticleWorkflowTransitionRequestSecurityContextResolver;
use Sulu\Bundle\AdminBundle\Metadata\GroupProviderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Content\Application\Security\WorkflowTransitionRequestSecurityContextResolverInterface;

#[CoversClass(ArticleWorkflowTransitionRequestSecurityContextResolver::class)]
class ArticleWorkflowTransitionRequestSecurityContextResolverTest extends TestCase
{
    use ProphecyTrait;

    public function testResolveDelegatesForOtherResources(): void
    {
        $condition = new SecurityCondition('sulu.global.snippets', 'en');

        $inner = $this->prophesize(WorkflowTransitionRequestSecurityContextResolverInterface::class);
        $inner->resolve('snippets', 'snippet-id-1', 'en')->willReturn($condition);

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $entityManager->getRepository(Argument::cetera())->shouldNotBeCalled();

        $resolver = $this->createResolver($inner->reveal(), $entityManager->reveal());

        $this->assertSame($condition, $resolver->resolve('snippets', 'snippet-id-1', 'en'));
    }

    public function testHasDelegates(): void
    {
        $inner = $this->prophesize(WorkflowTransitionRequestSecurityContextResolverInterface::class);
        $inner->has(ArticleInterface::RESOURCE_KEY)->willReturn(true);
        $inner->has('products')->willReturn(false);

        $resolver = $this->createResolver($inner->reveal(), $this->prophesize(EntityManagerInterface::class)->reveal());

        $this->assertTrue($resolver->has(ArticleInterface::RESOURCE_KEY));
        $this->assertFalse($resolver->has('products'));
    }

    private function createResolver(
        WorkflowTransitionRequestSecurityContextResolverInterface $inner,
        EntityManagerInterface $entityManager,
    ): ArticleWorkflowTransitionRequestSecurityContextResolver {
        return new ArticleWorkflowTransitionRequestSecurityContextResolver(
            $inner,
            new ArticleSecurityContextResolver(
                $entityManager,
                $this->prophesize(GroupProviderInterface::class)->reveal(),
                new MetadataProviderRegistry($this->prophesize(ContainerInterface::class)->reveal()),
            ),
        );
    }
}
