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

use Sulu\Bundle\TagBundle\Admin\TagAdmin;
use Sulu\Bundle\TagBundle\Controller\Exception\ConstraintViolationException;
use Sulu\Bundle\TagBundle\Tag\Exception\TagAlreadyExistsException;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Security\SecuredControllerInterface;
use Sulu\Component\Serializer\SuluSerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class TagCreateAction extends AbstractRestController implements SecuredControllerInterface
{
    protected static $entityName = \Sulu\Bundle\TagBundle\Entity\Tag::class;

    public function __construct(
        private SuluSerializerInterface $suluSerializer,
        private TagManagerInterface $tagManager,
    ) {}

    /**
     * Inserts a new tag.
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function __invoke(Request $request): Response
    {
        $name = $request->getPayload()->get('name');

        try {
            if (null == $name) {
                throw new MissingArgumentException(self::$entityName, 'name');
            }

            $tag = $this->tagManager->save($this->getData($request));

            return $this->suluSerializer->handleView($tag, ['partialTag']);
        } catch (TagAlreadyExistsException $exception) {
            $cvExistsException = new ConstraintViolationException(
                sprintf('A tag with the name "%s" already exists!', $exception->getName()),
                'name',
                ConstraintViolationException::EXCEPTION_CODE_NON_UNIQUE_NAME,
            );
            $data = $cvExistsException->toArray();

            return $this->suluSerializer->handleView($data, [], 400);
        } catch (RestException $exception) {
            return $this->suluSerializer->handleView($exception->toArray(), [], 400);
        }
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

    /**
     * @return string
     */
    public function getSecurityContext(): string
    {
        return TagAdmin::SECURITY_CONTEXT;
    }
}
