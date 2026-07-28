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

namespace Sulu\Page\Tests\Functional\Infrastructure\Sulu\Admin;

use PHPUnit\Framework\Attributes\DataProvider;
use Sulu\Bundle\AdminBundle\Admin\View\ViewRegistry;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Page\Infrastructure\Sulu\Admin\PageAdmin;

/**
 * A not-yet-saved locale must only expose the content tab; every other tab is revealed once the
 * locale has its own draft (and therefore a templateKey), mirroring the "create page" flow. This
 * prevents enabling a shadow on a locale that has no template yet.
 */
class PageAdminViewTest extends SuluTestCase
{
    private const LOCALE_PERSISTED_CONDITION = 'availableLocales && locale in availableLocales';

    private ViewRegistry $viewRegistry;

    protected function setUp(): void
    {
        $this->viewRegistry = self::getContainer()->get('sulu_admin.view_registry');
    }

    public function testContentTabIsAvailableForUnsavedLocale(): void
    {
        $condition = $this->viewRegistry
            ->findViewByName(PageAdmin::EDIT_FORM_VIEW . '.content')
            ->getOption('tabCondition');

        $this->assertIsString($condition);
        $this->assertStringNotContainsString(
            self::LOCALE_PERSISTED_CONDITION,
            $condition,
            'The content tab must stay available for a not-yet-saved locale.',
        );
    }

    /**
     * @return iterable<array{string}>
     */
    public static function nonContentTabProvider(): iterable
    {
        yield 'seo' => ['seo'];
        yield 'excerpt' => ['excerpt'];
        yield 'settings' => ['settings'];
        yield 'insights' => ['insights'];
        yield 'permissions' => ['permissions'];
    }

    #[DataProvider('nonContentTabProvider')]
    public function testNonContentTabRequiresPersistedLocale(string $tab): void
    {
        $condition = $this->viewRegistry
            ->findViewByName(PageAdmin::EDIT_FORM_VIEW . '.' . $tab)
            ->getOption('tabCondition');

        $this->assertIsString($condition, \sprintf('Tab "%s" must have a tabCondition.', $tab));
        $this->assertStringContainsString(
            self::LOCALE_PERSISTED_CONDITION,
            $condition,
            \sprintf('Tab "%s" must only be shown once the locale has been saved.', $tab),
        );
    }
}
