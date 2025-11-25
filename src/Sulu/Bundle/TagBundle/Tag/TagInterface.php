<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tag;

use Sulu\Component\Persistence\Model\AuditableInterface;

/**
 * Interface for tag.
 */
interface TagInterface extends AuditableInterface
{
    public const RESOURCE_KEY = 'tags';

    /**
     * Set name.
     */
    public function setName(string $name): static;

    /**
     * Get name.
     */
    public function getName(): string;

    /**
     * Get id.
     */
    public function getId(): int;

    /**
     * Set id.
     */
    public function setId(int $id): static;

    /**
     * Get string representation.
     */
    public function __toString(): string;
}
