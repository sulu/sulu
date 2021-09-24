<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider;

use JMS\Serializer\Annotation\Groups;

class RestoreConfiguration
{
    /**
     * @var string|null
     * @Groups({"frontend"})
     */
    private $form;

    /**
     * @var string|null
     * @Groups({"frontend"})
     */
    private $view;

    /**
     * @var array<string, string>|null
     * @Groups({"frontend"})
     */
    private $resultToView;

    /**
     * @param array<string, string>|null $resultToView
     */
    public function __construct(
        ?string $form = null,
        ?string $view = null,
        ?array $resultToView = null
    ) {
        $this->form = $form;
        $this->view = $view;
        $this->resultToView = $resultToView;
    }
}
