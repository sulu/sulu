<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Mapper;

use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupWebspaceRepositoryInterface;

/**
 * Service that maps data to target groups.
 */
class TargetGroupMapper implements TargetGroupMapperInterface
{
    /**
     * @var TargetGroupWebspaceRepositoryInterface
     */
    private $targetGroupWebspaceRepository;

    /**
     * @param TargetGroupWebspaceRepositoryInterface $targetGroupWebspaceRepository
     */
    public function __construct(TargetGroupWebspaceRepositoryInterface $targetGroupWebspaceRepository)
    {
        $this->targetGroupWebspaceRepository = $targetGroupWebspaceRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function mapDataToTargetGroup(TargetGroupInterface $targetGroup, array $data, $doPatch = true)
    {
        $targetGroup->setTitle($this->getConditionalValue($data, 'title', null, $targetGroup->getTitle(), $doPatch));
        $targetGroup->setPriority(
            $this->getConditionalValue($data, 'priority', null, $targetGroup->getPriority(), $doPatch)
        );
        $targetGroup->setActive((bool) json_decode($this->getValue($data, 'active', $targetGroup->isActive())));
        $targetGroup->setDescription(
            $this->getConditionalValue($data, 'description', null, $targetGroup->getDescription(), $doPatch)
        );
        $targetGroup->setAllWebspaces(
            (bool) json_decode($this->getValue($data, 'allWebspaces', $targetGroup->isAllWebspaces()))
        );

        $webspaces = $this->getConditionalValue($data, 'webspaces', [], false, $doPatch);
        if ($webspaces !== false) {
            $this->mapWebspaceData($targetGroup, $webspaces);
        }
    }

    /**
     * Maps webspace data to given target group.
     *
     * @param TargetGroupInterface $targetGroup
     * @param array $webspaceData
     */
    protected function mapWebspaceData(TargetGroupInterface $targetGroup, array $webspaceData)
    {
        // Remove all webspaces from targetGroup are not given.
        $webspaceKeys = array_column($webspaceData, 'webspaceKey');
        foreach ($targetGroup->getWebspaces() as $targetGroupWebspace) {
            if (!in_array($targetGroupWebspace->getWebspaceKey(), $webspaceKeys)) {
                $this->targetGroupWebspaceRepository->remove($targetGroupWebspace);
                $targetGroup->removeWebspace($targetGroupWebspace);
            }
        }

        foreach ($webspaceData as $data) {
            $webspaceKey = $this->getValue($data, 'webspaceKey', null);
            $targetGroupWebspace = $this->targetGroupWebspaceRepository->findOrCreate($targetGroup, $webspaceKey);
            // Add to webspaces if not already added.
            if (!$targetGroup->getWebspaces()->contains($targetGroupWebspace)) {
                $targetGroup->addWebspace($targetGroupWebspace);
            }
            $targetGroupWebspace->setWebspaceKey($webspaceKey);
            $targetGroupWebspace->setTargetGroup($targetGroup);
        }
    }

    /**
     * Returns property of data with given name.
     * If this property does not exists this function returns given default
     * based on if patch is true or false.
     *
     * @param array $data
     * @param string $name
     * @param mixed|null $putDefault
     * @param mixed|null $patchDefault
     * @param bool $doPatch
     *
     * @return mixed
     */
    protected function getConditionalValue(
        array $data,
        $name,
        $putDefault = null,
        $patchDefault = null,
        $doPatch = true
    ) {
        // Return default if array key does not exist.
        if (!array_key_exists($name, $data)) {
            if ($doPatch) {
                return $patchDefault;
            }

            return $putDefault;
        }

        return $data[$name];
    }

    /**
     * Returns value of data array, if key exists. Otherwise returns given default.
     *
     * @param array $data
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    protected function getValue(array $data, $name, $default = null)
    {
        // Return default if array key does not exist.
        if (!array_key_exists($name, $data)) {
            return $default;
        }

        return $data[$name];
    }
}
