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
use Sulu\Bundle\AudienceTargetingBundle\TargetGroup\TargetGroupEvaluatorInterface;
use Sulu\Bundle\AudienceTargetingBundle\TargetGroup\TargetGroupStoreInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for evaluating the target group based on the current request.
 */
class TargetGroupEvaluationController
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
     * @var TargetGroupStoreInterface
     */
    private $targetGroupStore;

    /**
     * @var string
     */
    private $targetGroupHeader;

    /**
     * @param TargetGroupEvaluatorInterface $targetGroupEvaluator
     * @param TargetGroupRepositoryInterface $targetGroupRepository
     * @param TargetGroupStoreInterface $targetGroupStore
     * @param string $targetGroupHeader
     */
    public function __construct(
        TargetGroupEvaluatorInterface $targetGroupEvaluator,
        TargetGroupRepositoryInterface $targetGroupRepository,
        TargetGroupStoreInterface $targetGroupStore,
        $targetGroupHeader
    ) {
        $this->targetGroupEvaluator = $targetGroupEvaluator;
        $this->targetGroupRepository = $targetGroupRepository;
        $this->targetGroupStore = $targetGroupStore;
        $this->targetGroupHeader = $targetGroupHeader;
    }

    /**
     * Takes the request and evaluates a target group based on the request.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function targetGroupAction(Request $request)
    {
        $currentTargetGroup = null;
        if ($request->headers->has($this->targetGroupHeader)) {
            $currentTargetGroup = $this->targetGroupRepository->find($request->headers->get($this->targetGroupHeader));
        }

        $targetGroup = $this->targetGroupEvaluator->evaluate(
            $currentTargetGroup ? TargetGroupRuleInterface::FREQUENCY_SESSION : TargetGroupRuleInterface::FREQUENCY_VISITOR,
            $currentTargetGroup
        );

        $response = new Response(null, 200, [
            $this->targetGroupHeader => $targetGroup ? $targetGroup->getId() : 0,
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
        $currentTargetGroup = $this->targetGroupRepository->find($this->targetGroupStore->getTargetGroupId(true));

        $targetGroup = $this->targetGroupEvaluator->evaluate(TargetGroupRuleInterface::FREQUENCY_HIT, $currentTargetGroup);
        $response = new Response();

        if ($targetGroup) {
            $this->targetGroupStore->updateTargetGroupId($targetGroup->getId());
        }

        return $response;
    }
}
