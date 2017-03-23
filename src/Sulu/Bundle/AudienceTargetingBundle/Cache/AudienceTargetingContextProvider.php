<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Cache;

use FOS\HttpCache\UserContext\ContextProviderInterface;
use FOS\HttpCache\UserContext\UserContext;
use Sulu\Bundle\AudienceTargetingBundle\Rule\TargetGroupEvaluatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * This class provides the audience targeting part of the UserContext for caching.
 */
class AudienceTargetingContextProvider implements ContextProviderInterface
{
    /**
     * @var TargetGroupEvaluatorInterface
     */
    private $targetGroupEvaluator;

    public function __construct(TargetGroupEvaluatorInterface $targetGroupEvaluator)
    {
        $this->targetGroupEvaluator = $targetGroupEvaluator;
    }

    /**
     * {@inheritdoc}
     */
    public function updateUserContext(UserContext $userContext)
    {
        $userContext->addParameter('audience-targeting', $this->targetGroupEvaluator->evaluate());
    }
}
