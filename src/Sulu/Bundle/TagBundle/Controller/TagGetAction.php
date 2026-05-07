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

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\View\ViewHandler;
use Sulu\Bundle\TagBundle\Admin\TagAdmin;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Response;

final class TagGetAction extends AbstractRestController implements SecuredControllerInterface
{
    public function __construct(
        private readonly ViewHandler $viewHandler,
        private readonly TagManagerInterface $tagManager,
    ) {
        parent::__construct($viewHandler);
    }

    public function __invoke(int $id): Response
    {
        $view = $this->responseGetById($id, function ($id) {
            return $this->tagManager->findById($id);
        });

        $context = new Context();
        $context->setGroups(['partialTag']);
        $view->setContext($context);

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
