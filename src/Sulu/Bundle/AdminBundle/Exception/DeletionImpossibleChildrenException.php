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

namespace Sulu\Bundle\AdminBundle\Exception;

use Sulu\Bundle\AdminBundle\Resource\SuluResource;
use Sulu\Component\Rest\Exception\AdditionalInformationExceptionInterface;

class DeletionImpossibleChildrenException extends \Exception implements AdditionalInformationExceptionInterface
{
    public const EXCEPTION_CODE = 12345; // TODO change

    /**
     * @var SuluResource
     */
    private $resource;

    /**
     * @var SuluResource[]
     */
    private $childResources;

    /**
     * @var int
     */
    private $totalChildren;

    /**
     * @param SuluResource[] $childResources
     */
    public function __construct(SuluResource $resource, array $childResources, int $totalChildren)
    {
        $this->resource = $resource;
        $this->childResources = $childResources;
        $this->totalChildren = $totalChildren;

        parent::__construct(
            \sprintf('Resource cannot be deleted, because it has %d children', $this->totalChildren),
            static::EXCEPTION_CODE
        );
    }

    public function getResource(): SuluResource
    {
        return $this->resource;
    }

    /**
     * @return SuluResource[]
     */
    public function getChildResources(): array
    {
        return $this->childResources;
    }

    public function getTotalChildren(): int
    {
        return $this->totalChildren;
    }

    public function getAdditionalInformation(): array
    {
        return [
            'children' => $this->childResources,
            'totalChildren' => $this->totalChildren,
        ];
    }
}
