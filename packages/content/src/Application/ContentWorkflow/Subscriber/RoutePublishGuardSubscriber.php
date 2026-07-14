<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Application\ContentWorkflow\Subscriber;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Content\Domain\Exception\PublishWithoutRouteException;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\RoutableInterface;
use Sulu\Content\Domain\Model\ShadowInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

/**
 * @final
 *
 * @internal this class is internal and should not be extended from or used in another context
 */
class RoutePublishGuardSubscriber implements EventSubscriberInterface
{
    public function __construct(private MetadataProviderRegistry $metadataProviderRegistry)
    {
    }

    public function guardPublish(GuardEvent $guardEvent): void
    {
        $dimensionContent = $guardEvent->getSubject();

        if (!$dimensionContent instanceof DimensionContentInterface
            || !$dimensionContent instanceof RoutableInterface
        ) {
            return;
        }

        if (!$dimensionContent::isRouteMandatory()) {
            return;
        }

        if (null !== $dimensionContent->getRoute()) {
            return;
        }

        // a shadow locale has no route of its own, its url is derived from the shadowed locale at publish time
        if ($dimensionContent instanceof ShadowInterface && $dimensionContent->getShadowLocale()) {
            return;
        }

        if (!$dimensionContent instanceof TemplateInterface) {
            return;
        }

        /** @var string|null $template */
        $template = $dimensionContent->getTemplateKey() ?? null;
        $locale = $dimensionContent->getLocale();

        if (!$template || !$locale) {
            return;
        }

        if (!$this->hasRouteProperty($dimensionContent::getTemplateType(), $template, $locale)) {
            return;
        }

        $guardEvent->addTransitionBlocker(new TransitionBlocker(
            'Content with a route property cannot be published without a route.',
            PublishWithoutRouteException::TRANSITION_BLOCKER_CODE
        ));
    }

    private function hasRouteProperty(string $templateType, string $template, string $locale): bool
    {
        $typedMetadata = $this->metadataProviderRegistry->getMetadataProvider('form')
            ->getMetadata($templateType, $locale, []);

        if (!$typedMetadata instanceof TypedFormMetadata) {
            return false;
        }

        $metadata = $typedMetadata->getForms()[$template] ?? null;

        if (!$metadata instanceof FormMetadata) {
            return false;
        }

        foreach ($metadata->getFlatFieldMetadata() as $property) {
            if ('route' === $property->getType()
                || 'page_tree_route' === $property->getType()
            ) {
                return true;
            }
        }

        return false;
    }

    public static function getSubscribedEvents(): array
    {
        $eventName = 'workflow.content_workflow.guard.' . WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH;

        return [
            $eventName => 'guardPublish',
        ];
    }
}
