<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Routing\Route;

class SearchAdmin extends Admin
{
    public function getRoutes(): array
    {
        return [
            (new Route('sulu_search.search', '/', 'sulu_search.search')),
        ];
    }
}
