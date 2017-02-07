<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\JsConfigInterface;
use Sulu\Bundle\ContentBundle\Markup\Link\LinkProviderPoolInterface;

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
        return 'sulu_content.link_provider.configuration';
    }
}
