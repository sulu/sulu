<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\Form;

use JMS\Serializer\Annotation\SerializedName;

class TypedForm
{
    /**
     * @var Form[]
     * @SerializedName("types")
     */
    private $forms = [];

    public function addForm($key, Form $form): void
    {
        $this->forms[$key] = $form;
    }

    public function getForms(): array
    {
        return $this->forms;
    }
}
