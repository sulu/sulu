<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * This Controller returns javascript templates available from the AdminBundle.
 */
class TemplateController
{
    /**
     * @var EngineInterface
     */
    private $templateEngine;

    /**
     * @param EngineInterface $templateEngine
     */
    public function __construct(EngineInterface $templateEngine)
    {
        $this->templateEngine = $templateEngine;
    }

    /**
     * Returns the javascript template for the CSV export overlay. Implemented this way to enable developers to override
     * this template using the built-in twig methods from Symfony.
     *
     * @return Response
     */
    public function csvExportFormAction()
    {
        return $this->templateEngine->renderResponse('SuluAdminBundle:CsvExport:form.html.twig');
    }
}
