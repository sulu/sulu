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

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationProviderInterface;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;

class CategoryContentNavigationProvider implements ContentNavigationProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getNavigationItems(array $options = array())
    {
        $details = new ContentNavigationItem('content-navigation.category.details');
        $details->setAction('details');
        $details->setComponent('categories@sulucategory');
        $details->setComponentOptions(array('display'=>'form'));

        return array($details);
    }
}
