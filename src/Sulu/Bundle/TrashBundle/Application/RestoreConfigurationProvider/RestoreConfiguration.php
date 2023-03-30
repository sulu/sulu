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
     * @Groups({"frontend"})
     */
    private ?string $form = null;

    /**
     * @Groups({"frontend"})
     */
    private ?string $view = null;

    /**
     * @var array<string, string>|null
     *
     * @Groups({"frontend"})
     */
    private ?array $resultToView = null;

    /**
     * @var array<string>|null
     *
     * @Groups({"frontend"})
     */
    private ?array $resultSerializationGroups = null;

    /**
     * @param array<string, string>|null $resultToView
     * @param array<string>|null $resultSerializationGroups
     */
    public function __construct(
        ?string $form = null,
        ?string $view = null,
        ?array $resultToView = null,
        ?array $resultSerializationGroups = null
    ) {
        $this->form = $form;
        $this->view = $view;
        $this->resultToView = $resultToView;
        $this->resultSerializationGroups = $resultSerializationGroups;
    }

    public function getForm(): ?string
    {
        return $this->form;
    }

    public function getView(): ?string
    {
        return $this->view;
    }

    /**
     * @return array<string, string>|null
     */
    public function getResultToView(): ?array
    {
        return $this->resultToView;
    }

    /**
     * @return array<string>|null
     */
    public function getResultSerializationGroups(): ?array
    {
        return $this->resultSerializationGroups;
    }
}
