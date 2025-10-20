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
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormGroup;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\GroupProvider;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class GroupProviderTest extends TestCase
{
    use ProphecyTrait;

    private GroupProvider $groupProvider;

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
        $this->metadataProvider = $this->prophesize(MetadataProviderInterface::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);

        $this->groupProvider = new GroupProvider($this->metadataProvider, $this->translator->reveal());
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

        $this->metadataProvider
            ->getMetadata(ArticleInterface::TEMPLATE_TYPE, '', [])
            ->willReturn($typedFormMetadata);

        $this->translator->trans(\Prophecy\Argument::any(), [], 'admin')
            ->willReturnArgument(0);

        $groups = $this->groupProvider->getGroups();

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

        $this->metadataProvider
            ->getMetadata(ArticleInterface::TEMPLATE_TYPE, '', [])
            ->willReturn($typedFormMetadata);

        $this->translator->trans(\Prophecy\Argument::any(), [], 'admin')
            ->willReturnArgument(0);

        $groups = $this->groupProvider->getGroups();

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

        $this->metadataProvider
            ->getMetadata(ArticleInterface::TEMPLATE_TYPE, '', [])
            ->willReturn($typedFormMetadata);

        $this->translator->trans(\Prophecy\Argument::any(), [], 'admin')
            ->willReturnArgument(0);

        $groups = $this->groupProvider->getGroups();

        $this->assertCount(1, $groups);
        $this->assertArrayHasKey('default', $groups);
        $this->assertSame('default', $groups['default']->identifier);
        $this->assertSame('Default', $groups['default']->title);
        $this->assertSame(['article'], $groups['default']->templates);
    }

    public function testGetGroupsWithEmptyForms(): void
    {
        $typedFormMetadata = new TypedFormMetadata();

        $this->metadataProvider
            ->getMetadata(ArticleInterface::TEMPLATE_TYPE, '', [])
            ->willReturn($typedFormMetadata);

        $groups = $this->groupProvider->getGroups();

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

        $this->metadataProvider
            ->getMetadata(ArticleInterface::TEMPLATE_TYPE, '', [])
            ->willReturn($typedFormMetadata);

        $this->translator->trans(\Prophecy\Argument::any(), [], 'admin')
            ->willReturnArgument(0);

        $groups = $this->groupProvider->getGroups();

        $this->assertCount(2, $groups);
        $this->assertArrayHasKey('content', $groups);
        $this->assertArrayHasKey('default', $groups);

        $this->assertSame(['article', 'news'], $groups['content']->templates);
        $this->assertSame(['blog'], $groups['default']->templates);
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
