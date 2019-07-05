<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Rule;

use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\OperatingSystem;
use Sulu\Bundle\AudienceTargetingBundle\Rule\Type\SingleSelect;
use Symfony\Component\Translation\TranslatorInterface;

class OperatingSystemRule implements RuleInterface
{
    const OPERATING_SYSTEM = 'os';

    private static $operatingSystems = ['Android', 'iOS', 'GNU/Linux', 'Mac', 'Windows'];

    /**
     * @var DeviceDetector
     */
    private $deviceDetector;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(DeviceDetector $deviceDetector, TranslatorInterface $translator)
    {
        $this->deviceDetector = $deviceDetector;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function evaluate(array $options)
    {
        if (!array_key_exists(static::OPERATING_SYSTEM, $options)) {
            return false;
        }

        $operatingSystem = OperatingSystem::getOsFamily($this->deviceDetector->getOs('short_name'));

        return $operatingSystem == $options[static::OPERATING_SYSTEM];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->translator->trans('sulu_audience_targeting.operating_system', [], 'admin');
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return new SingleSelect(static::OPERATING_SYSTEM, array_map(function($operatingSystem) {
            return [
                'id' => $operatingSystem,
                'name' => $operatingSystem,
            ];
        }, static::$operatingSystems));
    }
}
