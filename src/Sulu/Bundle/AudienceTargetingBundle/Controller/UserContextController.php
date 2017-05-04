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

use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleInterface;
use Sulu\Bundle\AudienceTargetingBundle\Rule\TargetGroupEvaluatorInterface;
use Sulu\Bundle\AudienceTargetingBundle\UserContext\UserContextStoreInterface;
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
     * @var TargetGroupRepositoryInterface
     */
    private $targetGroupRepository;

    /**
     * @var UserContextStoreInterface
     */
    private $userContextStore;

    /**
     * @var string
     */
    private $userContextHeader;

    /**
     * @param TargetGroupEvaluatorInterface $targetGroupEvaluator
     * @param TargetGroupRepositoryInterface $targetGroupRepository
     * @param UserContextStoreInterface $userContextStore
     * @param string $userContextHeader
     */
    public function __construct(
        TargetGroupEvaluatorInterface $targetGroupEvaluator,
        TargetGroupRepositoryInterface $targetGroupRepository,
        UserContextStoreInterface $userContextStore,
        $userContextHeader
    ) {
        $this->targetGroupEvaluator = $targetGroupEvaluator;
        $this->targetGroupRepository = $targetGroupRepository;
        $this->userContextStore = $userContextStore;
        $this->userContextHeader = $userContextHeader;
    }

    /**
     * Takes the request and calculates a user context hash based on the user.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function targetGroupAction(Request $request)
    {
        $currentTargetGroup = null;
        if ($request->headers->has($this->userContextHeader)) {
            $currentTargetGroup = $this->targetGroupRepository->find($request->headers->get($this->userContextHeader));
        }

        $targetGroup = $this->targetGroupEvaluator->evaluate(
            $currentTargetGroup ? TargetGroupRuleInterface::FREQUENCY_SESSION : TargetGroupRuleInterface::FREQUENCY_USER,
            $currentTargetGroup
        );

        $response = new Response(null, 200, [
            $this->userContextHeader => $targetGroup ? $targetGroup->getId() : 0,
        ]);

        return $response;
    }

    /**
     * This end point is called by the injected code on the website to update the target group on every hit.
     *
     * @return Response
     */
    public function targetGroupHitAction()
    {
        $currentTargetGroup = $this->targetGroupRepository->find($this->userContextStore->getUserContext());

        $targetGroup = $this->targetGroupEvaluator->evaluate(TargetGroupRuleInterface::FREQUENCY_HIT, $currentTargetGroup);
        $response = new Response();

        if ($targetGroup) {
            $this->userContextStore->updateUserContext($targetGroup->getId());
        }

        return $response;
    }
}
