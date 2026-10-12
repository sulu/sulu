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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Infrastructure\Sulu\Security\ArticleSecurityContextResolver;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormGroup;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\GroupProviderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;

#[CoversClass(ArticleSecurityContextResolver::class)]
class ArticleSecurityContextResolverTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @param array<string, FormGroup> $groups
     */
    #[DataProvider('provideTemplateKeys')]
    public function testResolveByTemplateKey(array $groups, ?string $templateKey, string $expected): void
    {
        $groupProvider = $this->prophesize(GroupProviderInterface::class);
        $groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE)->willReturn($groups);

        $resolver = new ArticleSecurityContextResolver(
            $this->prophesize(EntityManagerInterface::class)->reveal(),
            $groupProvider->reveal(),
            new MetadataProviderRegistry($this->prophesize(ContainerInterface::class)->reveal()),
        );

        $this->assertSame($expected, $resolver->resolveByTemplateKey($templateKey));
    }

    #[DataProvider('provideDefaultTypes')]
    public function testResolveByDefaultType(?string $defaultType, string $expected): void
    {
        $groups = [
            'default' => new FormGroup('default', 'Default', ['article']),
            'blog' => new FormGroup('blog', 'Blog', ['blog']),
        ];
        $groupProvider = $this->prophesize(GroupProviderInterface::class);
        $groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE)->willReturn($groups);

        $metadata = new TypedFormMetadata();
        if (null !== $defaultType) {
            $metadata->setDefaultType($defaultType);
        }
        $metadataProvider = $this->prophesize(MetadataProviderInterface::class);
        $metadataProvider->getMetadata(ArticleInterface::TEMPLATE_TYPE, 'en', [])->willReturn($metadata);
        $container = $this->prophesize(ContainerInterface::class);
        $container->has('form')->willReturn(true);
        $container->get('form')->willReturn($metadataProvider->reveal());

        $resolver = new ArticleSecurityContextResolver(
            $this->prophesize(EntityManagerInterface::class)->reveal(),
            $groupProvider->reveal(),
            new MetadataProviderRegistry($container->reveal()),
        );

        $this->assertSame($expected, $resolver->resolveByDefaultType('en'));
    }

    /**
     * @return iterable<string, array{string|null, string}>
     */
    public static function provideDefaultTypes(): iterable
    {
        yield 'default type of a group' => ['blog', 'sulu.article.articles_blog'];
        yield 'default type of the default group' => ['article', 'sulu.article.articles'];
        yield 'no default type' => [null, 'sulu.article.articles'];
    }

    public function testResolveByArticleOfASingleGroupDoesNotQuery(): void
    {
        $groupProvider = $this->prophesize(GroupProviderInterface::class);
        $groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE)
            ->willReturn(['blog' => new FormGroup('blog', 'Blog', ['blog'])]);
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $entityManager->getRepository(\Prophecy\Argument::any())->shouldNotBeCalled();

        $resolver = new ArticleSecurityContextResolver(
            $entityManager->reveal(),
            $groupProvider->reveal(),
            new MetadataProviderRegistry($this->prophesize(ContainerInterface::class)->reveal()),
        );

        $this->assertSame(['sulu.article.articles'], $resolver->resolveByArticle('id', ['en']));
    }

    /**
     * @return iterable<string, array{array<string, FormGroup>, string|null, string}>
     */
    public static function provideTemplateKeys(): iterable
    {
        $groups = [
            'default' => new FormGroup('default', 'Default', ['article']),
            'blog' => new FormGroup('blog', 'Blog', ['blog']),
        ];

        yield 'template of a group' => [$groups, 'blog', 'sulu.article.articles_blog'];
        yield 'template of the default group' => [$groups, 'article', 'sulu.article.articles'];
        yield 'template of no group' => [$groups, 'removed', 'sulu.article.articles'];
        yield 'no template' => [$groups, null, 'sulu.article.articles'];
        yield 'single group' => [['blog' => new FormGroup('blog', 'Blog', ['blog'])], 'blog', 'sulu.article.articles'];
    }
}
