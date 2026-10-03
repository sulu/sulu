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
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Infrastructure\Sulu\Security\ArticleWorkflowTransitionRequestSecurityContextResolver;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormGroup;
use Sulu\Bundle\AdminBundle\Metadata\GroupProviderInterface;
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

        $groupProvider = $this->prophesize(GroupProviderInterface::class);
        $groupProvider->getGroups(Argument::cetera())->shouldNotBeCalled();

        $resolver = new ArticleWorkflowTransitionRequestSecurityContextResolver(
            $inner->reveal(),
            $this->prophesize(EntityManagerInterface::class)->reveal(),
            $groupProvider->reveal(),
        );

        $this->assertSame($condition, $resolver->resolve('snippets', 'snippet-id-1', 'en'));
    }

    public function testResolveDelegatesWithoutQueryingWhenThereIsASingleGroup(): void
    {
        $condition = new SecurityCondition('sulu.article.articles', 'en');

        $inner = $this->prophesize(WorkflowTransitionRequestSecurityContextResolverInterface::class);
        $inner->resolve(ArticleInterface::RESOURCE_KEY, 'article-id-1', 'en')->willReturn($condition);

        $groupProvider = $this->prophesize(GroupProviderInterface::class);
        $groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE)
            ->willReturn(['default' => new FormGroup('default', 'Default', ['article'])]);

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $entityManager->getRepository(Argument::cetera())->shouldNotBeCalled();

        $resolver = new ArticleWorkflowTransitionRequestSecurityContextResolver(
            $inner->reveal(),
            $entityManager->reveal(),
            $groupProvider->reveal(),
        );

        $this->assertSame($condition, $resolver->resolve(ArticleInterface::RESOURCE_KEY, 'article-id-1', 'en'));
    }

    public function testHasDelegates(): void
    {
        $inner = $this->prophesize(WorkflowTransitionRequestSecurityContextResolverInterface::class);
        $inner->has(ArticleInterface::RESOURCE_KEY)->willReturn(true);
        $inner->has('products')->willReturn(false);

        $resolver = new ArticleWorkflowTransitionRequestSecurityContextResolver(
            $inner->reveal(),
            $this->prophesize(EntityManagerInterface::class)->reveal(),
            $this->prophesize(GroupProviderInterface::class)->reveal(),
        );

        $this->assertTrue($resolver->has(ArticleInterface::RESOURCE_KEY));
        $this->assertFalse($resolver->has('products'));
    }
}
