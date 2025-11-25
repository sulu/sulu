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

namespace Sulu\Bundle\TrashBundle\Domain\Model;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;

#[ExclusionPolicy('all')]
class TrashItemTranslation
{
    private int $id;

    private TrashItemInterface $trashItem;

    #[Expose]
    #[Groups(['trash_item_admin_api'])]
    private ?string $locale;

    #[Expose]
    #[Groups(['trash_item_admin_api'])]
    private string $title;

    public function __construct(TrashItemInterface $trashItem, ?string $locale, string $title)
    {
        $this->trashItem = $trashItem;
        $this->locale = $locale;
        $this->title = $title;
    }

    public function getTrashItem(): TrashItemInterface
    {
        return $this->trashItem;
    }

    public function setTrashItem(TrashItemInterface $trashItem): static
    {
        $this->trashItem = $trashItem;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }
}
