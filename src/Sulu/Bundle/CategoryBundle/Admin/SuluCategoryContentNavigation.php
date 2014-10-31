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

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigation;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;

class SuluCategoryContentNavigation extends ContentNavigation
{
    public function __construct()
    {
        parent::__construct();

        $this->setName('Category');

        // details
        $details = new ContentNavigationItem('content-navigation.category.details');
        $details->setAction('details');
        $details->setGroups(array('category'));
        $details->setComponent('categories@sulucategory');
        $details->setComponentOptions(array('display'=>'form'));
        $this->addNavigationItem($details);
    }
}
