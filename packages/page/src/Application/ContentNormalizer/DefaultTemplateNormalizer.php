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

namespace Sulu\Page\Application\ContentNormalizer;

use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Content\Application\ContentNormalizer\Normalizer\NormalizerInterface;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;

/**
 * @internal this class is internal and should not be extended from or used in another context
 */
final class DefaultTemplateNormalizer implements NormalizerInterface
{
    public function __construct(
        private WebspaceManagerInterface $webspaceManager,
    ) {
    }

    public function enhance(object $object, array $normalizedData): array
    {
        if (!$object instanceof PageDimensionContentInterface) {
            return $normalizedData;
        }

        if (!empty($normalizedData['template'])) {
            return $normalizedData;
        }

        $page = $object->getResource();

        $webspace = $this->webspaceManager->findWebspaceByKey($page->getWebspaceKey());
        $defaultTemplate = $webspace?->getDefaultTemplate(PageInterface::TEMPLATE_TYPE);

        if (null !== $defaultTemplate && '' !== $defaultTemplate) {
            $normalizedData['template'] = $defaultTemplate;
        }

        return $normalizedData;
    }

    public function getIgnoredAttributes(object $object): array
    {
        return [];
    }
}
