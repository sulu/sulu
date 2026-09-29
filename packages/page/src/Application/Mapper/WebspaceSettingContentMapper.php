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

namespace Sulu\Page\Application\Mapper;

use Sulu\Content\Application\ContentPersister\ContentPersisterInterface;
use Sulu\Page\Domain\Model\WebspaceSettingInterface;
use Webmozart\Assert\Assert;

/**
 * @internal This class should be instantiated inside a project.
 *           Use the message to create or modify a webspace setting.
 *           Create an own Mapper to extend the mapper with
 *           custom logic.
 */
final class WebspaceSettingContentMapper implements WebspaceSettingMapperInterface
{
    public function __construct(private ContentPersisterInterface $contentPersister)
    {
    }

    public function mapWebspaceSettingData(WebspaceSettingInterface $webspaceSetting, array $data): void
    {
        $locale = $data['locale'] ?? null;
        Assert::string($locale);

        $this->contentPersister->persist($webspaceSetting, $data, ['locale' => $locale]);
    }
}
