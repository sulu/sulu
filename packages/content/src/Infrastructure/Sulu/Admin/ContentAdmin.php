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

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Content\Application\RequestWorkflow\RequestWorkflowResolverInterface;

/**
 * Publishes the one content fact the admin cannot derive on its own: which templates a review
 * workflow covers. Saved content answers that through `workflowTransitionRequestEnabled`, but the
 * create form has nothing saved to ask, so it has to go by the template the author picked.
 *
 * @internal
 */
final class ContentAdmin extends Admin
{
    public const CONFIG_KEY = 'sulu_content';

    /**
     * @param array<string, string> $templateTypes template type per resource key
     */
    public function __construct(
        private readonly RequestWorkflowResolverInterface $requestWorkflowResolver,
        private readonly array $templateTypes,
    ) {
    }

    public function getConfigKey(): string
    {
        return self::CONFIG_KEY;
    }

    /**
     * @return array{requestWorkflowTemplates: array<string, list<string>>}
     */
    public function getConfig(): array
    {
        $requestWorkflowTemplates = [];
        foreach ($this->templateTypes as $resourceKey => $templateType) {
            $requestWorkflowTemplates[$resourceKey] = $this->requestWorkflowResolver
                ->resolveTemplateKeysWithWorkflow($resourceKey, $templateType);
        }

        return ['requestWorkflowTemplates' => $requestWorkflowTemplates];
    }
}
