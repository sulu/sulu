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

class DeletionImpossibleChildPermissionsException extends \Exception implements AdditionalInformationExceptionInterface
{
    public const EXCEPTION_CODE = 12346; // TODO change

    /**
     * @var SuluResource
     */
    private $resource;

    /**
     * @var SuluResource[]
     */
    private $unauthorizedChildResources;

    /**
     * @var int
     */
    private $totalUnauthorizedChildren;

    /**
     * @param SuluResource[] $unauthorizedChildResources
     */
    public function __construct(SuluResource $resource, array $unauthorizedChildResources, int $totalUnauthorizedChildren)
    {
        $this->resource = $resource;
        $this->unauthorizedChildResources = $unauthorizedChildResources;
        $this->totalUnauthorizedChildren = $totalUnauthorizedChildren;

        parent::__construct(
            \sprintf(
                'Resource cannot be deleted, because the user doesn\'t have permissions for %d children',
                $this->totalUnauthorizedChildren
            ),
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
    public function getUnauthorizedChildResources(): array
    {
        return $this->unauthorizedChildResources;
    }

    public function getTotalUnauthorizedChildren(): int
    {
        return $this->totalUnauthorizedChildren;
    }

    public function getAdditionalInformation(): array
    {
        return [
            'unauthorizedChildren' => $this->unauthorizedChildResources,
            'totalUnauthorizedChildren' => $this->totalUnauthorizedChildren,
        ];
    }
}
