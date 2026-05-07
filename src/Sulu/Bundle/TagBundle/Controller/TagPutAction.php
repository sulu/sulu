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
use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Bundle\MarkupBundle\Tag\TagNotFoundException;
use Sulu\Bundle\TagBundle\Admin\TagAdmin;
use Sulu\Bundle\TagBundle\Controller\Exception\ConstraintViolationException;
use Sulu\Bundle\TagBundle\Tag\Exception\TagAlreadyExistsException;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class TagPutAction extends AbstractRestController implements SecuredControllerInterface
{
    public function __construct(private ViewHandlerInterface $viewHandler, private TagManagerInterface $tagManager)
    {
        parent::__construct($viewHandler);
    }

    public function __invoke(Request $request, int $id): Response
    {
        $name = $request->getPayload()->get('name');
        try {
            if (null == $name) {
                throw new MissingArgumentException(self::$entityName, 'name');
            }

            $tag = $this->tagManager->save($this->getData($request), $id);

            $context = new Context();
            $context->setGroups(['partialTag']);
            $view = $this->view($tag)->setContext($context);
        } catch (TagAlreadyExistsException $exc) {
            $cvExistsException = new ConstraintViolationException(
                'A tag with the name "' . $exc->getName() . '"already exists!',
                'name',
                ConstraintViolationException::EXCEPTION_CODE_NON_UNIQUE_NAME,
            );
            $view = $this->view($cvExistsException->toArray(), 400);
        } catch (TagNotFoundException $exc) {
            $entityNotFoundException = new EntityNotFoundException(self::$entityName, $id);
            $view = $this->view($entityNotFoundException->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * @return string
     */
    public function getSecurityContext(): string
    {
        return TagAdmin::SECURITY_CONTEXT;
    }

    /**
     * Get data.
     *
     * @return array
     */
    protected function getData(Request $request): array
    {
        return $request->request->all();
    }
}
