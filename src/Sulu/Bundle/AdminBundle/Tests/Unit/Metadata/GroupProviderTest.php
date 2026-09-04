<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormGroup;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\GroupProvider;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;

class GroupProviderTest extends TestCase
{
    use ProphecyTrait;

    private GroupProvider $groupProvider;

    private MetadataProviderRegistry $metadataProviderRegistry;

    /**
     * @var ObjectProphecy<ContainerInterface>
     */
    private ObjectProphecy $container;

    /**
     * @var ObjectProphecy<MetadataProviderInterface>
     */
    private ObjectProphecy $metadataProvider;

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    private ObjectProphecy $translator;

    protected function setUp(): void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->metadataProvider = $this->prophesize(MetadataProviderInterface::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $this->translator->getLocale()->willReturn('en');

        $this->metadataProviderRegistry = new MetadataProviderRegistry($this->container->reveal());
        $this->groupProvider = new GroupProvider($this->metadataProviderRegistry, $this->translator->reveal());
    }

    public function testGetGroupsWithSingleGroup(): void
    {
        $formMetadata1 = new FormMetadata();
        $formMetadata1->setKey('article');
        $formMetadata1->setGroup('content');

        $formMetadata2 = new FormMetadata();
        $formMetadata2->setKey('blog');
        $formMetadata2->setGroup('content');

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm('article', $formMetadata1);
        $typedFormMetadata->addForm('blog', $formMetadata2);

        $this->container->has('form')->willReturn(true);
        $this->container->get('form')->willReturn($this->metadataProvider->reveal());

        $this->metadataProvider
            ->getMetadata(ArticleInterface::TEMPLATE_TYPE, '', [])
            ->willReturn($typedFormMetadata);

        $this->translator->trans(\Prophecy\Argument::any(), [], 'admin')
            ->willReturnArgument(0);

        $groups = $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE);

        $this->assertCount(1, $groups);
        $this->assertArrayHasKey('content', $groups);
        $this->assertEquals(FormGroup::class, \get_class($groups['content']));
        $this->assertSame('content', $groups['content']->identifier);
        $this->assertSame('Content', $groups['content']->title);
        $this->assertSame(['article', 'blog'], $groups['content']->templates);
    }

    public function testGetGroupsWithMultipleGroups(): void
    {
        $formMetadata1 = new FormMetadata();
        $formMetadata1->setKey('article');
        $formMetadata1->setGroup('content');

        $formMetadata2 = new FormMetadata();
        $formMetadata2->setKey('blog');
        $formMetadata2->setGroup('blog');

        $formMetadata3 = new FormMetadata();
        $formMetadata3->setKey('news');
        $formMetadata3->setGroup('news');

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm('article', $formMetadata1);
        $typedFormMetadata->addForm('blog', $formMetadata2);
        $typedFormMetadata->addForm('news', $formMetadata3);

        $this->container->has('form')->willReturn(true);
        $this->container->get('form')->willReturn($this->metadataProvider->reveal());

        $this->metadataProvider
            ->getMetadata(ArticleInterface::TEMPLATE_TYPE, '', [])
            ->willReturn($typedFormMetadata);

        $this->translator->trans(\Prophecy\Argument::any(), [], 'admin')
            ->willReturnArgument(0);

        $groups = $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE);

        $this->assertCount(3, $groups);
        $this->assertArrayHasKey('content', $groups);
        $this->assertArrayHasKey('blog', $groups);
        $this->assertArrayHasKey('news', $groups);

        $this->assertSame('content', $groups['content']->identifier);
        $this->assertSame('Content', $groups['content']->title);
        $this->assertSame(['article'], $groups['content']->templates);

        $this->assertSame('blog', $groups['blog']->identifier);
        $this->assertSame('Blog', $groups['blog']->title);
        $this->assertSame(['blog'], $groups['blog']->templates);

        $this->assertSame('news', $groups['news']->identifier);
        $this->assertSame('News', $groups['news']->title);
        $this->assertSame(['news'], $groups['news']->templates);
    }

    public function testGetGroupsWithDefaultGroup(): void
    {
        $formMetadata1 = new FormMetadata();
        $formMetadata1->setKey('article');
        // No group set, should fall back to default

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm('article', $formMetadata1);

        $this->container->has('form')->willReturn(true);
        $this->container->get('form')->willReturn($this->metadataProvider->reveal());

        $this->metadataProvider
            ->getMetadata(ArticleInterface::TEMPLATE_TYPE, '', [])
            ->willReturn($typedFormMetadata);

        $this->translator->trans(\Prophecy\Argument::any(), [], 'admin')
            ->willReturnArgument(0);

        $groups = $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE);

        $this->assertCount(1, $groups);
        $this->assertArrayHasKey('default', $groups);
        $this->assertSame('default', $groups['default']->identifier);
        $this->assertSame('Default', $groups['default']->title);
        $this->assertSame(['article'], $groups['default']->templates);
    }

    public function testGetGroupsWithEmptyForms(): void
    {
        $typedFormMetadata = new TypedFormMetadata();

        $this->container->has('form')->willReturn(true);
        $this->container->get('form')->willReturn($this->metadataProvider->reveal());

        $this->metadataProvider
            ->getMetadata(ArticleInterface::TEMPLATE_TYPE, '', [])
            ->willReturn($typedFormMetadata);

        $groups = $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE);

        $this->assertCount(0, $groups);
        $this->assertEmpty($groups);
    }

    public function testGetGroupsWithMixedGroupsAndDefault(): void
    {
        $formMetadata1 = new FormMetadata();
        $formMetadata1->setKey('article');
        $formMetadata1->setGroup('content');

        $formMetadata2 = new FormMetadata();
        $formMetadata2->setKey('blog');
        // No group set, should fall back to default

        $formMetadata3 = new FormMetadata();
        $formMetadata3->setKey('news');
        $formMetadata3->setGroup('content');

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm('article', $formMetadata1);
        $typedFormMetadata->addForm('blog', $formMetadata2);
        $typedFormMetadata->addForm('news', $formMetadata3);

        $this->container->has('form')->willReturn(true);
        $this->container->get('form')->willReturn($this->metadataProvider->reveal());

        $this->metadataProvider
            ->getMetadata(ArticleInterface::TEMPLATE_TYPE, '', [])
            ->willReturn($typedFormMetadata);

        $this->translator->trans(\Prophecy\Argument::any(), [], 'admin')
            ->willReturnArgument(0);

        $groups = $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE);

        $this->assertCount(2, $groups);
        $this->assertArrayHasKey('content', $groups);
        $this->assertArrayHasKey('default', $groups);

        $this->assertSame(['article', 'news'], $groups['content']->templates);
        $this->assertSame(['blog'], $groups['default']->templates);
    }

    public function testResolveGroupReturnsIdentifierOfTheGroupContainingTheTemplateKey(): void
    {
        $typedFormMetadata = new TypedFormMetadata();
        foreach (['article' => 'content', 'blog' => 'blog', 'news' => 'blog'] as $key => $group) {
            $formMetadata = new FormMetadata();
            $formMetadata->setKey($key);
            $formMetadata->setGroup($group);
            $typedFormMetadata->addForm($key, $formMetadata);
        }

        $this->container->has('form')->willReturn(true);
        $this->container->get('form')->willReturn($this->metadataProvider->reveal());

        $this->metadataProvider
            ->getMetadata(ArticleInterface::TEMPLATE_TYPE, '', [])
            ->willReturn($typedFormMetadata);

        $this->translator->trans(\Prophecy\Argument::any(), [], 'admin')
            ->willReturnArgument(0);

        $this->assertSame('content', $this->groupProvider->resolveGroup(ArticleInterface::TEMPLATE_TYPE, 'article'));
        $this->assertSame('blog', $this->groupProvider->resolveGroup(ArticleInterface::TEMPLATE_TYPE, 'news'));
    }

    public function testResolveGroupFallsBackToDefaultGroupWhenTemplateKeyIsUnknown(): void
    {
        $typedFormMetadata = new TypedFormMetadata();
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('article');
        $formMetadata->setGroup('content');
        $typedFormMetadata->addForm('article', $formMetadata);

        $this->container->has('form')->willReturn(true);
        $this->container->get('form')->willReturn($this->metadataProvider->reveal());

        $this->metadataProvider
            ->getMetadata(ArticleInterface::TEMPLATE_TYPE, '', [])
            ->willReturn($typedFormMetadata);

        $this->translator->trans(\Prophecy\Argument::any(), [], 'admin')
            ->willReturnArgument(0);

        $this->assertSame(
            'default',
            $this->groupProvider->resolveGroup(ArticleInterface::TEMPLATE_TYPE, 'unknown-template'),
        );
    }

    public function testGetGroupsAreSortedAlphabeticallyByTitle(): void
    {
        $typedFormMetadata = new TypedFormMetadata();
        foreach (['news' => 'news', 'article' => 'content', 'blog' => 'blog'] as $key => $group) {
            $formMetadata = new FormMetadata();
            $formMetadata->setKey($key);
            $formMetadata->setGroup($group);
            $typedFormMetadata->addForm($key, $formMetadata);
        }

        $this->container->has('form')->willReturn(true);
        $this->container->get('form')->willReturn($this->metadataProvider->reveal());

        $this->metadataProvider
            ->getMetadata(ArticleInterface::TEMPLATE_TYPE, '', [])
            ->willReturn($typedFormMetadata);

        $this->translator->trans(\Prophecy\Argument::any(), [], 'admin')
            ->willReturnArgument(0);

        $groups = $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE);

        $this->assertSame(['blog', 'content', 'news'], \array_keys($groups));
    }

    public function testGetGroupsPlacesDefaultGroupFirst(): void
    {
        $typedFormMetadata = new TypedFormMetadata();
        foreach (['news' => 'news', 'article' => null, 'blog' => 'blog'] as $key => $group) {
            $formMetadata = new FormMetadata();
            $formMetadata->setKey($key);
            $formMetadata->setGroup($group);
            $typedFormMetadata->addForm($key, $formMetadata);
        }

        $this->container->has('form')->willReturn(true);
        $this->container->get('form')->willReturn($this->metadataProvider->reveal());

        $this->metadataProvider
            ->getMetadata(ArticleInterface::TEMPLATE_TYPE, '', [])
            ->willReturn($typedFormMetadata);

        $this->translator->trans(\Prophecy\Argument::any(), [], 'admin')
            ->willReturnArgument(0);

        $groups = $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE);

        $this->assertSame(['default', 'blog', 'news'], \array_keys($groups));
    }

    public function testGetGroupsAreSortedByTitleNotByIdentifier(): void
    {
        $typedFormMetadata = new TypedFormMetadata();
        foreach (['alpha-template' => 'alpha', 'zebra-template' => 'zebra'] as $key => $group) {
            $formMetadata = new FormMetadata();
            $formMetadata->setKey($key);
            $formMetadata->setGroup($group);
            $typedFormMetadata->addForm($key, $formMetadata);
        }

        $this->container->has('form')->willReturn(true);
        $this->container->get('form')->willReturn($this->metadataProvider->reveal());

        $this->metadataProvider
            ->getMetadata(ArticleInterface::TEMPLATE_TYPE, '', [])
            ->willReturn($typedFormMetadata);

        $this->translator->trans(\Prophecy\Argument::exact('sulu_admin.template_group.alpha'), [], 'admin')
            ->willReturn('Zebra Group');
        $this->translator->trans(\Prophecy\Argument::exact('sulu_admin.template_group.zebra'), [], 'admin')
            ->willReturn('Alpha Group');

        $groups = $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE);

        $this->assertSame(['zebra', 'alpha'], \array_keys($groups));
    }

    public function testGetGroupsFallBackToTheIdentifierWhenTitlesAreEqual(): void
    {
        $typedFormMetadata = new TypedFormMetadata();
        foreach (['zebra-template' => 'zebra', 'alpha-template' => 'alpha'] as $key => $group) {
            $formMetadata = new FormMetadata();
            $formMetadata->setKey($key);
            $formMetadata->setGroup($group);
            $typedFormMetadata->addForm($key, $formMetadata);
        }

        $this->container->has('form')->willReturn(true);
        $this->container->get('form')->willReturn($this->metadataProvider->reveal());

        $this->metadataProvider
            ->getMetadata(ArticleInterface::TEMPLATE_TYPE, '', [])
            ->willReturn($typedFormMetadata);

        $this->translator->trans(\Prophecy\Argument::any(), [], 'admin')->willReturn('Same Title');

        $groups = $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE);

        $this->assertSame(['alpha', 'zebra'], \array_keys($groups));
    }

    /**
     * @param string[] $expectedOrder
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('collationProvider')]
    public function testGetGroupsSortNonAsciiTitlesWithTheCollationOfTheTranslatorLocale(string $locale, array $expectedOrder): void
    {
        if (!\class_exists(\Collator::class)) {
            $this->markTestSkipped('The intl extension is required for locale aware collation.');
        }

        $typedFormMetadata = new TypedFormMetadata();
        foreach (['zoo-template' => 'zoo', 'doctors-template' => 'doctors'] as $key => $group) {
            $formMetadata = new FormMetadata();
            $formMetadata->setKey($key);
            $formMetadata->setGroup($group);
            $typedFormMetadata->addForm($key, $formMetadata);
        }

        $this->container->has('form')->willReturn(true);
        $this->container->get('form')->willReturn($this->metadataProvider->reveal());

        $this->metadataProvider
            ->getMetadata(ArticleInterface::TEMPLATE_TYPE, '', [])
            ->willReturn($typedFormMetadata);

        $this->translator->getLocale()->willReturn($locale);
        $this->translator->trans(\Prophecy\Argument::exact('sulu_admin.template_group.zoo'), [], 'admin')
            ->willReturn('Zoo');
        $this->translator->trans(\Prophecy\Argument::exact('sulu_admin.template_group.doctors'), [], 'admin')
            ->willReturn('Ärzte');

        $groups = $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE);

        $this->assertSame($expectedOrder, \array_keys($groups));
    }

    /**
     * @return array<string, array{string, string[]}>
     */
    public static function collationProvider(): array
    {
        return [
            'german_sorts_umlaut_with_its_base_letter' => ['de', ['doctors', 'zoo']],
            'swedish_sorts_umlaut_after_z' => ['sv', ['zoo', 'doctors']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('groupTitleProvider')]
    public function testGetGroupTitle(string $groupIdentifier, string $expectedTitle): void
    {
        // Mock translator to return the key itself (simulating no translation found)
        $this->translator->trans(\Prophecy\Argument::any(), [], 'admin')->willReturnArgument(0);

        // Use reflection to test the private method
        $reflection = new \ReflectionClass($this->groupProvider);
        $method = $reflection->getMethod('getGroupTitle');
        $method->setAccessible(true);

        $result = $method->invoke($this->groupProvider, $groupIdentifier);

        $this->assertSame($expectedTitle, $result);
    }

    public function testGetGroupTitleWithTranslation(): void
    {
        // Mock translator to return a custom translation
        $this->translator->trans(\Prophecy\Argument::any(), [], 'admin')
            ->willReturn('Blog Articles');

        // Use reflection to test the private method
        $reflection = new \ReflectionClass($this->groupProvider);
        $method = $reflection->getMethod('getGroupTitle');
        $method->setAccessible(true);

        $result = $method->invoke($this->groupProvider, 'blog');

        $this->assertSame('Blog Articles', $result);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function groupTitleProvider(): array
    {
        return [
            'simple_group' => ['content', 'Content'],
            'lowercase_group' => ['blog', 'Blog'],
            'uppercase_group' => ['NEWS', 'NEWS'],
            'mixed_case_group' => ['myGroup', 'MyGroup'],
            'hyphenated_group' => ['my-group', 'My-group'],
            'underscore_group' => ['my_group', 'My_group'],
            'single_char' => ['a', 'A'],
            'empty_string' => ['', ''],
        ];
    }
}
