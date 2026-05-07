<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Controller;

use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Bundle\MarkupBundle\Tag\TagNotFoundException;
use Sulu\Bundle\TagBundle\Admin\TagAdmin;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Response;

final class TagDeleteAction extends AbstractRestController implements SecuredControllerInterface
{
    public function __construct(
        private readonly ViewHandlerInterface $viewHandler,
        private readonly TagManagerInterface $tagManager,
    ) {
        parent::__construct($viewHandler);
    }

    public function __invoke(int $id): Response
    {
        $delete = function ($id) {
            try {
                $this->tagManager->delete($id);
            } catch (TagNotFoundException $notFoundException) {
                throw new EntityNotFoundException(self::$entityName, $id, $notFoundException);
            }
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * @return string
     */
    public function getSecurityContext(): string
    {
        return TagAdmin::SECURITY_CONTEXT;
    }
}
