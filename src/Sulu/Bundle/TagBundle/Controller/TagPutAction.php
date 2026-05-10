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
use Sulu\Component\Serializer\SuluSerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class TagPutAction extends AbstractRestController implements SecuredControllerInterface
{
    public function __construct(
        private SuluSerializerInterface $suluSerializer,
        private TagManagerInterface $tagManager,
    ) {}

    public function __invoke(Request $request, int $id): Response
    {
        $name = $request->getPayload()->get('name');
        try {
            if (null == $name) {
                throw new MissingArgumentException(self::$entityName, 'name');
            }

            $tag = $this->tagManager->save($this->getData($request), $id);

            return $this->suluSerializer->handleView($tag, ['partialTag']);
        } catch (TagAlreadyExistsException $exception) {
            $cvExistsException = new ConstraintViolationException(
                sprintf('A tag with the name "%s" already exists!', $exception->getName()),
                'name',
                ConstraintViolationException::EXCEPTION_CODE_NON_UNIQUE_NAME,
            );

            return $this->suluSerializer->handleView($cvExistsException->toArray(), [], 400);
        } catch (TagNotFoundException $exc) {
            $exception = new EntityNotFoundException(self::$entityName, $id);

            return $this->suluSerializer->handleView($exception->toArray(), [], 404);
        } catch (RestException $restException) {
            return $this->suluSerializer->handleView($restException->toArray, [], 400);
        }
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
