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

namespace Sulu\Snippet\Tests\Functional\Infrastructure\Sulu\Admin;

use Sulu\Bundle\AdminBundle\Admin\View\ResourceViewUrlGeneratorInterface;
use Sulu\Bundle\AdminBundle\Metadata\GroupProviderInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Infrastructure\Sulu\Admin\SnippetResourceViewParameterProvider;
use Sulu\Snippet\Tests\Traits\CreateSnippetTrait;

class SnippetResourceViewParameterProviderTest extends SuluTestCase
{
    use CreateSnippetTrait;

    private SnippetResourceViewParameterProvider $provider;

    protected function setUp(): void
    {
        /** @var GroupProviderInterface $groupProvider */
        $groupProvider = self::getContainer()->get('sulu_admin.metadata_group_provider');
        $this->provider = new SnippetResourceViewParameterProvider($this->getEntityManager(), $groupProvider);
        $this->purgeDatabase();
    }

    public function testGetResourceKey(): void
    {
        $this->assertSame(SnippetInterface::RESOURCE_KEY, SnippetResourceViewParameterProvider::getResourceKey());
    }

    public function testGetViewParametersWithGroupedTemplate(): void
    {
        $snippet = static::createSnippet([
            'en' => ['draft' => ['template' => 'snippet-alternate', 'title' => 'Alternate Snippet']],
        ]);

        $this->assertSame(
            ['group' => 'alternate-group'],
            $this->provider->getViewParameters('detail', ['id' => $snippet->getUuid(), 'locale' => 'en']),
        );
    }

    public function testGetViewParametersWithUngroupedTemplate(): void
    {
        $snippet = static::createSnippet([
            'en' => ['draft' => ['template' => 'snippet', 'title' => 'Snippet']],
        ]);

        $this->assertSame(
            ['group' => GroupProviderInterface::DEFAULT_GROUP],
            $this->provider->getViewParameters('detail', ['id' => $snippet->getUuid(), 'locale' => 'en']),
        );
    }

    public function testGetViewParametersUsesTemplateOfGivenLocale(): void
    {
        $snippet = static::createSnippet([
            'en' => ['draft' => ['template' => 'snippet', 'title' => 'Snippet']],
            'de' => ['draft' => ['template' => 'snippet-alternate', 'title' => 'Alternate Snippet']],
        ]);

        $this->assertSame(
            ['group' => GroupProviderInterface::DEFAULT_GROUP],
            $this->provider->getViewParameters('detail', ['id' => $snippet->getUuid(), 'locale' => 'en']),
        );
        $this->assertSame(
            ['group' => 'alternate-group'],
            $this->provider->getViewParameters('detail', ['id' => $snippet->getUuid(), 'locale' => 'de']),
        );
    }

    public function testGetViewParametersUsesDraftTemplate(): void
    {
        $snippet = static::createSnippet([
            'en' => [
                'live' => ['template' => 'snippet', 'title' => 'Snippet'],
                'draft' => ['template' => 'snippet-alternate', 'title' => 'Alternate Snippet'],
            ],
        ]);

        $this->assertSame(
            ['group' => 'alternate-group'],
            $this->provider->getViewParameters('detail', ['id' => $snippet->getUuid(), 'locale' => 'en']),
        );
    }

    public function testGetViewParametersWithoutLocaleUsesFirstLocale(): void
    {
        $snippet = static::createSnippet([
            'en' => ['draft' => ['template' => 'snippet', 'title' => 'Snippet']],
            'de' => ['draft' => ['template' => 'snippet-alternate', 'title' => 'Alternate Snippet']],
        ]);

        $this->assertSame(
            ['group' => 'alternate-group'],
            $this->provider->getViewParameters('detail', ['id' => $snippet->getUuid()]),
        );
    }

    public function testGetViewParametersWithUnknownSnippet(): void
    {
        $this->assertSame(
            ['group' => GroupProviderInterface::DEFAULT_GROUP],
            $this->provider->getViewParameters('detail', ['id' => '00000000-0000-0000-0000-000000000000', 'locale' => 'en']),
        );
    }

    public function testGetViewParametersForOtherView(): void
    {
        $snippet = static::createSnippet([
            'en' => ['draft' => ['template' => 'snippet-alternate', 'title' => 'Alternate Snippet']],
        ]);

        $this->assertSame([], $this->provider->getViewParameters('list', ['id' => $snippet->getUuid(), 'locale' => 'en']));
    }

    public function testResourceViewUrlGeneratorResolvesConfiguredDetailView(): void
    {
        $snippet = static::createSnippet([
            'en' => ['draft' => ['template' => 'snippet-alternate', 'title' => 'Grouped Snippet']],
        ]);

        /** @var ResourceViewUrlGeneratorInterface $resourceViewUrlGenerator */
        $resourceViewUrlGenerator = self::getContainer()->get('test.sulu_admin.resource_view_url_generator');

        $this->assertSame(
            '/admin/#/snippets/en/alternate-group/' . $snippet->getUuid(),
            $resourceViewUrlGenerator->generate(SnippetInterface::RESOURCE_KEY, 'detail', ['id' => $snippet->getUuid(), 'locale' => 'en']),
        );
    }
}
