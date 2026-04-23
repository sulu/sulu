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

namespace Sulu\Page\Tests\Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Sulu\Page\Domain\Model\Page as SuluPage;

#[ORM\Entity(repositoryClass: NestedTreeRepository::class)]
#[ORM\Table(name: 'pa_pages')]
class Page extends SuluPage
{
    #[ORM\Column(name: 'custom_text', type: 'string', length: 255, nullable: true)]
    private ?string $customText = null;

    public function getCustomText(): ?string
    {
        return $this->customText;
    }

    public function setCustomText(?string $customText): void
    {
        $this->customText = $customText;
    }
}
