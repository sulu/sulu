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

namespace Sulu\Content\Tests\Unit\Content\Infrastructure\Sulu\Security;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Security\Authorization\AccessControl\SecuredEntityInterface;
use Sulu\Content\Infrastructure\Sulu\Security\WorkflowTransitionRequestSecurityContextResolver;

#[CoversClass(WorkflowTransitionRequestSecurityContextResolver::class)]
class WorkflowTransitionRequestSecurityContextResolverTest extends TestCase
{
    use ProphecyTrait;

    public function testResolveTakesTheDeclaredContextOfTheResource(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);

        $resolver = new WorkflowTransitionRequestSecurityContextResolver($entityManager->reveal(), [
            'articles' => ['security_context' => 'sulu.article.articles'],
            'snippets' => ['security_context' => 'sulu.global.snippets'],
        ]);

        $condition = $resolver->resolve('articles', 'article-id-1', 'en');

        $this->assertSame('sulu.article.articles', $condition->getSecurityContext());
        $this->assertSame('en', $condition->getLocale());
        $this->assertSame('sulu.global.snippets', $resolver->resolve('snippets', 'snippet-id-1', 'de')->getSecurityContext());
    }

    public function testResolveTakesTheContextOfTheEntityWhenTheResourceIsSecuredPerObject(): void
    {
        $entity = new SecuredEntityStub();

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $entityManager->find(SecuredEntityStub::class, 'page-id-1')->willReturn($entity);

        $resolver = new WorkflowTransitionRequestSecurityContextResolver($entityManager->reveal(), [
            'pages' => ['security_context' => 'sulu.webspaces.#webspace#', 'security_class' => SecuredEntityStub::class],
        ]);

        $condition = $resolver->resolve('pages', 'page-id-1', 'en');

        $this->assertSame('sulu.webspaces.example', $condition->getSecurityContext());
        $this->assertSame(SecuredEntityStub::class, $condition->getObjectType());
        $this->assertSame('page-id-1', $condition->getObjectId());
    }

    public function testResolveThrowsWhenTheResourceDeclaresNoContext(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);

        $resolver = new WorkflowTransitionRequestSecurityContextResolver($entityManager->reveal(), [
            'products' => ['routes' => ['detail' => 'app.get_product']],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Add it to "sulu_admin.resources.products"');

        $resolver->resolve('products', 'anything', 'en');
    }

    public function testResolveThrowsWhenTheSecuredEntityIsGone(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $entityManager->find(SecuredEntityStub::class, 'page-id-1')->willReturn(null);

        $resolver = new WorkflowTransitionRequestSecurityContextResolver($entityManager->reveal(), [
            'pages' => ['security_context' => 'sulu.webspaces.#webspace#', 'security_class' => SecuredEntityStub::class],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('with id "page-id-1": it does not exist');

        $resolver->resolve('pages', 'page-id-1', 'en');
    }

    public function testHasTellsWhetherTheResourceDeclaresAContext(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);

        $resolver = new WorkflowTransitionRequestSecurityContextResolver($entityManager->reveal(), [
            'articles' => ['security_context' => 'sulu.article.articles'],
            'products' => ['routes' => ['detail' => 'app.get_product']],
        ]);

        $this->assertTrue($resolver->has('articles'));
        $this->assertFalse($resolver->has('products'));
        $this->assertFalse($resolver->has('unknown'));
    }
}

class SecuredEntityStub implements SecuredEntityInterface
{
    public function getId(): string
    {
        return 'page-id-1';
    }

    public function getSecurityContext(): string
    {
        return 'sulu.webspaces.example';
    }
}
