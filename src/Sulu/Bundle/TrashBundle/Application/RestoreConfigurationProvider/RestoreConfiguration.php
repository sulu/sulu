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
     */
    #[Groups(['frontend'])]
    private $form;

    /**
     * @var string|null
     */
    #[Groups(['frontend'])]
    private $view;

    /**
     * @var array<string, string>|null
     */
    #[Groups(['frontend'])]
    private $resultToView;

    /**
     * @var array<string>|null
     */
    #[Groups(['frontend'])]
    private $resultSerializationGroups;

    /**
     * @var array<string, string>|null
     */
    #[Groups(['frontend'])]
    private $resultToViewName;

    /**
     * @param array<string, string>|null $resultToView
     * @param array<string>|null $resultSerializationGroups
     * @param array<string, string>|null $resultToViewName
     */
    public function __construct(
        ?string $form = null,
        ?string $view = null,
        ?array $resultToView = null,
        ?array $resultSerializationGroups = null,
        ?array $resultToViewName = null
    ) {
        $this->form = $form;
        $this->view = $view;
        $this->resultToView = $resultToView;
        $this->resultSerializationGroups = $resultSerializationGroups;
        $this->resultToViewName = $resultToViewName;
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

    /**
     * @return array<string, string>|null
     */
    public function getResultToViewName(): ?array
    {
        return $this->resultToViewName;
    }
}
