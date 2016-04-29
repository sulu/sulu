<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview;

use Doctrine\Common\Cache\Cache;
use Sulu\Bundle\PreviewBundle\Preview\Exception\ProviderNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\TokenNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderInterface;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\PreviewRendererInterface;

/**
 * Provider functionality to render and update preview instances.
 */
class Preview implements PreviewInterface
{
    /**
     * @var int
     */
    private $cacheLifeTime;

    /**
     * @var PreviewObjectProviderInterface[]
     */
    private $objectProviders;

    /**
     * @var Cache
     */
    private $dataCache;

    /**
     * @var PreviewRendererInterface
     */
    private $renderer;

    /**
     * @param PreviewObjectProviderInterface[] $objectProviders
     * @param Cache $dataCache
     * @param PreviewRendererInterface $renderer
     * @param int $cacheLifeTime
     */
    public function __construct(
        array $objectProviders,
        Cache $dataCache,
        PreviewRendererInterface $renderer,
        $cacheLifeTime = 3600
    ) {
        $this->objectProviders = $objectProviders;
        $this->dataCache = $dataCache;
        $this->renderer = $renderer;
        $this->cacheLifeTime = $cacheLifeTime;
    }

    /**
     * {@inheritdoc}
     */
    public function start($objectClass, $id, $userId, $webspaceKey, $locale, array $data = [])
    {
        $provider = $this->getProvider($objectClass);
        $object = $provider->getObject($id, $locale);
        $token = md5(sprintf('%s.%s.%s', $id, $locale, $userId));

        if (0 !== count($data)) {
            $provider->setValues($object, $locale, $data);
        }

        $this->save($token, $object);

        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function stop($token)
    {
        if (!$this->exists($token)) {
            return;
        }

        $this->dataCache->delete($token);
    }

    /**
     * {@inheritdoc}
     */
    public function exists($token)
    {
        return $this->dataCache->contains($token);
    }

    /**
     * {@inheritdoc}
     */
    public function update($token, $webspaceKey, $locale, array $data)
    {
        if (0 === count($data)) {
            return [];
        }

        $object = $this->fetch($token);
        $provider = $this->getProvider(get_class($object));
        $provider->setValues($object, $locale, $data);
        $this->save($token, $object);

        $id = $provider->getId($object);
        $html = $this->renderer->render($object, $id, $webspaceKey, $locale, true);

        $extractor = new RdfaExtractor($html);

        return $extractor->getPropertyValues(array_keys($data));
    }

    /**
     * {@inheritdoc}
     */
    public function updateContext($token, $webspaceKey, $locale, array $context, array $data)
    {
        $object = $this->fetch($token);
        $provider = $this->getProvider(get_class($object));
        if (0 === count($context)) {
            $id = $provider->getId($object);

            return $this->renderer->render($object, $id, $webspaceKey, $locale);
        }

        // context
        $object = $provider->setContext($object, $locale, $context);
        $id = $provider->getId($object);

        if (0 < count($data)) {
            // data
            $provider->setValues($object, $locale, $data);
        }

        $this->save($token, $object);

        return $this->renderer->render($object, $id, $webspaceKey, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function render($token, $webspaceKey, $locale)
    {
        $object = $this->fetch($token);
        $id = $this->getProvider(get_class($object))->getId($object);

        return $this->renderer->render($object, $id, $webspaceKey, $locale);
    }

    /**
     * Returns provider for given object-class.
     *
     * @param string $objectClass
     *
     * @return mixed|PreviewObjectProviderInterface
     *
     * @throws ProviderNotFoundException
     */
    protected function getProvider($objectClass)
    {
        if (!array_key_exists($objectClass, $this->objectProviders)) {
            throw new ProviderNotFoundException($objectClass);
        }

        return $this->objectProviders[$objectClass];
    }

    /**
     * Save the object.
     *
     * @param string $token
     * @param string $object
     *
     * @throws ProviderNotFoundException
     */
    protected function save($token, $object)
    {
        $data = $this->getProvider(get_class($object))->serialize($object);
        $data = sprintf("%s\n%s", get_class($object), $data);

        $this->dataCache->save($token, $data, $this->cacheLifeTime);
    }

    /**
     * Fetch the object.
     *
     * @param string $token
     *
     * @return mixed
     *
     * @throws ProviderNotFoundException
     * @throws TokenNotFoundException
     */
    protected function fetch($token)
    {
        if (!$this->exists($token)) {
            throw new TokenNotFoundException($token);
        }

        $cacheEntry = explode("\n", $this->dataCache->fetch($token), 2);

        return $this->getProvider($cacheEntry[0])->deserialize($cacheEntry[1], $cacheEntry[0]);
    }
}
