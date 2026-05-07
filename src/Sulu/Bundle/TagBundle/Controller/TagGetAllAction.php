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
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Bundle\TagBundle\Tag\TagRepositoryInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\RestHelperInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class TagGetAllAction extends AbstractRestController
{
    public function __construct(
        private readonly ViewHandlerInterface $viewHandler,
        private readonly RestHelperInterface $restHelper,
        private readonly FieldDescriptorFactoryInterface $fieldDescriptor,
        private DoctrineListBuilderFactoryInterface $listBuilderFactory,
        private TagRepositoryInterface $tagRepository,
        private readonly string $tagClass,
    ) {
        parent::__construct(viewHandler: $viewHandler);
    }

    public function __invoke(Request $request): Response
    {
        if ('true' === $request->query->get('flat')) {
            $fieldDescriptors = $this->fieldDescriptor->getFieldDescriptors('tags');
            $listBuilder = $this->listBuilderFactory->create($this->tagClass);

            $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

            $names = \array_filter(\explode(',', $request->query->get('names', '')));

            if (\count($names) > 0) {
                $listBuilder->in($fieldDescriptors['name'], $names);
                $listBuilder->limit(\count($names));
            }

            $idsParam = $request->query->get('ids', '');
            $ids = \array_filter(\explode(',', \is_string($idsParam) ? $idsParam : ''));
            if (\count($ids) > 0 && isset($fieldDescriptors['id'])) {
                $listBuilder->in($fieldDescriptors['id'], $ids);
                $listBuilder->limit(\count($ids));
            }

            $list = new PaginatedRepresentation(
                $listBuilder->execute(),
                TagInterface::RESOURCE_KEY,
                (int) $listBuilder->getCurrentPage(),
                (int) $listBuilder->getLimit(),
                (int) $listBuilder->count(),
            );

            $view = $this->view($list, 200);
            return $this->handleView($view);
        }

        $list = new CollectionRepresentation($this->tagRepository->findAll(), TagInterface::RESOURCE_KEY);

        $context = new Context();
        $context->setGroups(['partialTag']);
        $view = $this->view($list, 200)->setContext($context);

        return $this->handleView($view);
    }
}
