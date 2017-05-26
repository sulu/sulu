<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationProviderInterface;

/**
 * Class for snippet content navigation.
 */
class SnippetContentNavigationProvider implements ContentNavigationProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getNavigationItems(array $options = [])
    {
        $details = new ContentNavigationItem('content-navigation.snippets.details');
        $details->setAction('details');
        $details->setPosition(10);
        $details->setComponent('snippet/form/details@sulusnippet');

        $excerpt = new ContentNavigationItem('content-navigation.snippets.taxonomies');
        $excerpt->setId('tab-excerpt');
        $excerpt->setPosition(20);
        $excerpt->setAction('excerpt');
        $excerpt->setComponent('snippet/form/excerpt@sulusnippet');
        $excerpt->setDisplay(['edit']);

        return [$details, $excerpt];
    }
}
