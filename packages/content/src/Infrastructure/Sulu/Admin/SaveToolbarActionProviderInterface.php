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

/**
 * Adds entries to the dropdown of the save button of a content form, for example a bundle that offers one more way to
 * publish. Tag the service with `sulu_content.save_toolbar_action_provider`.
 *
 * The entries are appended behind the ones of the core. A provider decides itself for which content it answers. An entry
 * carries its own `visible_condition`. The option `permission` names a permission the user needs on the content, `edit`
 * or `live`: the core then adds the condition its caller uses for that permission to the one of the entry, and drops the
 * entry for users without the permission where it knows the security context.
 *
 * @experimental
 */
interface SaveToolbarActionProviderInterface
{
    /**
     * @param class-string<ContentRichEntityInterface> $contentRichEntityClass
     *
     * @return list<ToolbarAction>
     */
    public function getToolbarActions(string $contentRichEntityClass): array;
}
