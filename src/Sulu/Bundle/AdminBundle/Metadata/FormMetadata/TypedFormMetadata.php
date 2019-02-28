<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata;

use JMS\Serializer\Annotation\SerializedName;

class TypedFormMetadata
{
    /**
     * @var FormMetadata[]
     * @SerializedName("types")
     */
    private $forms = [];

    public function addForm($key, FormMetadata $form): void
    {
        $this->forms[$key] = $form;
    }

    public function getForms(): array
    {
        return $this->forms;
    }
}
