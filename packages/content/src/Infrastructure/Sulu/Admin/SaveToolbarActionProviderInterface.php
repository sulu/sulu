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

namespace Sulu\Content\Infrastructure\Sulu\Admin;

use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * Adds entries to the dropdown of the save button of a content form, for example one more way to publish.
 * Autoconfigured with the tag `sulu_content.save_toolbar_action_provider`.
 *
 * The entries are appended behind the core's and hidden while a request is open. The option `permission`, `edit` or
 * `live`, adds the caller's condition for it and drops the entry for users without it where the security context is
 * known; any other value throws.
 *
 * @experimental
 */
interface SaveToolbarActionProviderInterface
{
    /**
     * @template T of DimensionContentInterface
     *
     * @param class-string<ContentRichEntityInterface<T>> $contentRichEntityClass
     *
     * @return list<ToolbarAction>
     */
    public function getToolbarActions(string $contentRichEntityClass): array;
}
