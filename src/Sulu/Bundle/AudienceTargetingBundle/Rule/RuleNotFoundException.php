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

/**
 * This exception is thrown when a rule is tried to access, which does not exist.
 */
class RuleNotFoundException extends \RuntimeException
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;

        parent::__construct(sprintf('The rule with the name "%s" could not be found.', $this->name));
    }

    /**
     * Returns the name of the rule, which cannot be found.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
