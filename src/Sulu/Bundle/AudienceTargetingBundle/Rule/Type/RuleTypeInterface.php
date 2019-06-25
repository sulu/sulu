<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Rule\Type;

/**
 * Describes the type of a rule. Has an influence on the appearance in the administration interface.
 */
interface RuleTypeInterface
{
    /**
     * Returns a unique name along all the different rule types available.
     */
    public function getName(): string;

    public function getOptions(): array;
}
