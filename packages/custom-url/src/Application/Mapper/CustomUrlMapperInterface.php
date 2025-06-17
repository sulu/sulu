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

namespace Sulu\CustomUrl\Application\Mapper;

use Sulu\CustomUrl\Domain\Model\CustomUrlInterface;

interface CustomUrlMapperInterface
{
    public const SERVICE_TAG = 'sulu_custom_url.snippet_mapper';

    /**
     * @param array<mixed> $data
     */
    public function mapCustomUrlData(CustomUrlInterface $customUrl, array $data): void;
}
