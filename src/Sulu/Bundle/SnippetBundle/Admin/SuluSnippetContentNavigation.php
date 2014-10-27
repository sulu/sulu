<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigation;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;

/**
 * Class for snippet content navigation
 */
class SuluSnippetContentNavigation extends ContentNavigation
{
    /**
     * Construct
     */
    public function __construct()
    {
        parent::__construct();

        $this->setName('Snippets');

        // details
        $details = new ContentNavigationItem('content-navigation.snippets.details');
        $details->setAction('details');
        $details->setGroups(array('snippet'));
        $details->setComponent('snippet/form/details@sulusnippet');
        $this->addNavigationItem($details);
    }
}
