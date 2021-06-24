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

namespace Sulu\Bundle\TrashBundle\Domain\Exception;

class TrashItemNotFoundException extends \Exception
{
    /**
     * @var mixed[]
     */
    private $criteria;

    /**
     * @param mixed[] $criteria
     */
    public function __construct(array $criteria)
    {
        $this->criteria = $criteria;

        parent::__construct(
            \sprintf('TrashItem with criteria (%s) not found.', \json_encode($this->criteria))
        );
    }

    /**
     * @return mixed[]
     */
    public function getCriteria(): array
    {
        return $this->criteria;
    }
}
