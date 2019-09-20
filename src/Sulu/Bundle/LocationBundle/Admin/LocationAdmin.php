<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;

class LocationAdmin extends Admin
{
    public function getConfig(): ?array
    {
        return [
            'provider' => 'leaflet'
        ];
    }

    public function getConfigKey(): ?string
    {
        return 'sulu_location';
    }
}
