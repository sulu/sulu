<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Controller;

use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleInterface;
use Sulu\Bundle\AudienceTargetingBundle\Rule\TargetGroupEvaluatorInterface;
use Sulu\Component\HttpCache\HttpCache;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller responsible for creating a user context hash based on the audience targeting groups of the user,
 * which is recognized by a cookie.
 */
class UserContextController
{
    /**
     * @var TargetGroupEvaluatorInterface
     */
    private $targetGroupEvaluator;

    /**
     * @var string
     */
    private $hashHeader;

    /**
     * @param TargetGroupEvaluatorInterface $targetGroupEvaluator
     * @param string $hashHeader
     */
    public function __construct(TargetGroupEvaluatorInterface $targetGroupEvaluator, $hashHeader)
    {
        $this->targetGroupEvaluator = $targetGroupEvaluator;
        $this->hashHeader = $hashHeader;
    }

    /**
     * Takes the request and calculates a user context hash based on the user.
     */
    public function targetGroupAction()
    {
        $targetGroup = $this->targetGroupEvaluator->evaluate();

        $response = new Response(null, 200, [
            $this->hashHeader => $targetGroup ? $targetGroup->getId() : 0,
        ]);

        return $response;
    }

    /**
     * This end point is called by the injected code on the website to update the target group on every hit.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function targetGroupHitAction(Request $request)
    {
        $targetGroup = $this->targetGroupEvaluator->evaluate(TargetGroupRuleInterface::FREQUENCY_HIT);
        $response = new Response();
        if (!$targetGroup) {
            return $response;
        }

        if ($request->cookies->get(HttpCache::USER_CONTEXT_COOKIE) !== (string) $targetGroup->getId()
        ) {
            // only set cookie if new target group has higher priority
            $response->headers->setCookie(
                new Cookie(HttpCache::USER_CONTEXT_COOKIE, $targetGroup->getId(), HttpCache::USER_CONTEXT_COOKIE_LIFETIME)
            );
        }

        return $response;
    }
}
