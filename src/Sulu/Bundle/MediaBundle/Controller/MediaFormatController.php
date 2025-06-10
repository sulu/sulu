<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Bundle\MediaBundle\Media\FormatOptions\FormatOptionsManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\RequestParametersTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MediaFormatController extends AbstractRestController
{
    use RequestParametersTrait;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        private FormatOptionsManagerInterface $formatOptionsManager,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct($viewHandler);
    }

    /**
     * Returns all format resources.
     *
     * @param int $id
     *
     * @return Response
     */
    public function cgetAction($id, Request $request)
    {
        $formatOptions = $this->formatOptionsManager->getAll($id);

        return $this->handleView($this->view(\count($formatOptions) > 0 ? $formatOptions : new \stdClass()));
    }

    /**
     * Edits a format resource.
     *
     * @param int $id
     * @param string $key
     *
     * @return Response
     */
    public function putAction($id, $key, Request $request)
    {
        $options = $request->request->all();

        if (empty($options)) {
            $this->formatOptionsManager->delete($id, $key);
        } else {
            $this->formatOptionsManager->save($id, $key, $options);
        }
        $this->entityManager->flush();

        $formatOptions = $this->formatOptionsManager->get($id, $key);

        return $this->handleView($this->view($formatOptions));
    }

    /**
     * @param int $id
     *
     * @return Response
     */
    public function cpatchAction($id, Request $request)
    {
        $formatOptions = $request->request->all();
        foreach ($formatOptions as $formatKey => $formatOption) {
            if (empty($formatOption)) {
                $this->formatOptionsManager->delete($id, $formatKey);
                continue;
            }

            $this->formatOptionsManager->save($id, $formatKey, $formatOption);
        }

        $this->entityManager->flush();

        return $this->handleView($this->view($formatOptions));
    }
}
