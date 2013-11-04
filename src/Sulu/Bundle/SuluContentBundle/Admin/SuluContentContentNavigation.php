<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\ContentNavigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;

class SuluContentContentNavigation extends ContentNavigation
{

    public function __construct()
    {
        parent::__construct();

        $this->setName('Content');
        $this->setHeader(array(
            'title'         => 'back to contents',
            'displayOption' => 'link',
            'action'        => 'content/contents'
        ));

        $details = new NavigationItem('Details');
        $details->setContentType('contact');
        $details->setAction('details');
        $details->setType('content');
        $details->setDisplayOptions(array('edit'));

        $this->addNavigationItem($details);
    }
}
