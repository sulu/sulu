<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\JsConfigInterface;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderPoolInterface;

/**
 * Passes the link-provider configuration into the js-config.
 */
class LinkProviderJsConfig implements JsConfigInterface
{
    /**
     * @var LinkProviderPoolInterface
     */
    private $linkProviderPool;

    /**
     * @param LinkProviderPoolInterface $linkProviderPool
     */
    public function __construct(LinkProviderPoolInterface $linkProviderPool)
    {
        $this->linkProviderPool = $linkProviderPool;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        return $this->linkProviderPool->getConfiguration();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_page.link_provider.configuration';
    }
}
