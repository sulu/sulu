<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\ContentNavigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;

class SuluCategoryContentNavigation extends ContentNavigation
{

    public function __construct()
    {
        parent::__construct();

        $this->setName('Category');

        /* Category */
        // details
        $details = new NavigationItem('content-navigation.category.details');
        $details->setAction('details');
        $details->setContentType('category');
        $details->setContentComponent('categories@sulucategory');
        $details->setContentComponentOptions(array('display'=>'form'));
        $this->addNavigationItem($details);
    }
}
