<?php

declare(strict_types=1);

namespace Sulu\Component\Content\Types;

use Sulu\Bundle\MarkupBundle\Markup\Link\LinkItem;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderPoolInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\SimpleContentType;

/**
 * Link selection content type for linking to different providers.
 */
class Link extends SimpleContentType
{
    public const LINK_TYPE_EXTERNAL = 'external';

    /**
     * @var LinkProviderPoolInterface
     */
    private $providerPool;

    public function __construct(LinkProviderPoolInterface $providerPool)
    {
        parent::__construct('LinkProvider');

        $this->providerPool = $providerPool;
    }

    /**
     * {@inheritdoc}
     */
    protected function encodeValue($value)
    {
        return \json_encode($value);
    }

    /**
     * {@inheritdoc}
     */
    protected function decodeValue($value)
    {
        if (null === $value) {
            return null;
        }

        return \json_decode($value, true);
    }

    public function getViewData(PropertyInterface $property)
    {
        $value = $property->getValue();

        if (!$value) {
            return [];
        }

        return [
            'target' => $value['target'],
            'provider' => $value['provider'],
            'locale' => $value['locale'],
        ];
    }

    public function getContentData(PropertyInterface $property)
    {
        $value = $property->getValue();

        if (!$value || !isset($value['provider'])) {
            return null;
        }

        if ($value['provider'] === self::LINK_TYPE_EXTERNAL) {
            return $value['href'];
        }

        $provider = $this->providerPool->getProvider($value['provider']);

        $linkItem = $provider->preload([$value['href']], $value['locale'], true);

        if (\count($linkItem) === 0) {
            return [];
        }

        return reset($linkItem)->getUrl();
    }
}
