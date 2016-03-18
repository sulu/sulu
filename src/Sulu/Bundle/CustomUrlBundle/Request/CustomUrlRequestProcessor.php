<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\Request;

use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\CustomUrl\Manager\CustomUrlManagerInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Set localization in case of custom-url route.
 */
class CustomUrlRequestProcessor implements RequestProcessorInterface
{
    /**
     * @var CustomUrlManagerInterface
     */
    private $customUrlManager;

    public function __construct(CustomUrlManagerInterface $customUrlManager)
    {
        $this->customUrlManager = $customUrlManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestAttributes $requestAttributes)
    {
        $resourceLocator = rtrim(sprintf('%s%s', $request->getHost(), $request->getRequestUri()), '/');
        if (substr($resourceLocator, -5, 5) === '.html') {
            $resourceLocator = substr($resourceLocator, 0, -5);
        }

        $webspace = $requestAttributes->getAttribute('webspace');

        if (!$webspace) {
            return new RequestAttributes();
        }

        $routeDocument = $this->customUrlManager->findRouteByUrl(
            $resourceLocator,
            $webspace->getKey()
        );

        if (!$routeDocument || $routeDocument->isHistory()) {
            return new RequestAttributes(['customUrlRoute' => $routeDocument]);
        }

        $customUrlDocument = $this->customUrlManager->findByUrl(
            $resourceLocator,
            $requestAttributes->getAttribute('webspace')->getKey(),
            $routeDocument->getTargetDocument()->getTargetLocale()
        );

        if ($customUrlDocument === null
            || $customUrlDocument->isPublished() === false
            || $customUrlDocument->getTargetDocument() === null
            || $customUrlDocument->getTargetDocument()->getWorkflowStage() !== WorkflowStage::PUBLISHED
        ) {
            return new RequestAttributes(['customUrlRoute' => $routeDocument, 'customUrl' => $customUrlDocument]);
        }

        $localization = $this->parse($customUrlDocument->getTargetLocale());
        $request->setLocale($localization->getLocalization());

        return new RequestAttributes(
            ['localization' => $localization, 'customUrlRoute' => $routeDocument, 'customUrl' => $customUrlDocument]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function validate(RequestAttributes $attributes)
    {
        return true;
    }

    /**
     * Converts locale string to localization object.
     *
     * @param string $locale E.g. de_at or de.
     *
     * @return Localization
     */
    private function parse($locale)
    {
        $parts = explode('_', $locale);

        $localization = new Localization();
        $localization->setLanguage($parts[0]);
        if (count($parts) > 1) {
            $localization->setCountry($parts[1]);
        }

        return $localization;
    }
}
