<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * handles templates for this bundles.
 */
class TemplateController extends Controller
{
    /**
     * renders template for the collection settings-view.
     *
     * @return Response
     */
    public function collectionSettingsAction()
    {
        return $this->render('SuluMediaBundle:Template:collection-settings.html.twig');
    }

    /**
     * renders template for the new-collection-form.
     *
     * @return Response
     */
    public function collectionNewAction()
    {
        return $this->render('SuluMediaBundle:Template:collection-new.html.twig');
    }

    /**
     * renders template for a media in the multiple-edit form.
     *
     * @return Response
     */
    public function multipleEditAction()
    {
        return $this->render('SuluMediaBundle:Template:media-multiple-edit.html.twig');
    }
}
