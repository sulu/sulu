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
use Sulu\Bundle\AudienceTargetingBundle\Rule\Type\SingleSelect;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This rule determines from which type of device the request have been sent.
 */
class DeviceTypeRule implements RuleInterface
{
    public const DEVICE_TYPE = 'device_type';

    public const SMARTPHONE = 'smartphone';

    public const TABLET = 'tablet';

    public const DESKTOP = 'desktop';

    private static $deviceTypes = [self::SMARTPHONE, self::TABLET, self::DESKTOP];

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

    public function evaluate(array $options)
    {
        if (!\array_key_exists(static::DEVICE_TYPE, $options)) {
            return false;
        }

        return match ($options[static::DEVICE_TYPE]) {
            static::SMARTPHONE => $this->deviceDetector->isSmartphone(),
            static::TABLET => $this->deviceDetector->isTablet(),
            static::DESKTOP => $this->deviceDetector->isDesktop(),
            default => false,
        };
    }

    public function getName()
    {
        return $this->translator->trans('sulu_audience_targeting.device_type', [], 'admin');
    }

    public function getType()
    {
        return new SingleSelect(static::DEVICE_TYPE, \array_map(function($deviceTypes) {
            return [
                'id' => $deviceTypes,
                'name' => $this->translator->trans(
                    'sulu_audience_targeting.' . $deviceTypes,
                    [],
                    'admin'
                ),
            ];
        }, static::$deviceTypes));
    }
}
