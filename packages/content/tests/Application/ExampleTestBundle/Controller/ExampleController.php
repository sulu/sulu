<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Application\ExampleTestBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Content\Tests\Application\ExampleTestBundle\Repository\ExampleRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ExampleController extends AbstractRestController
{
    public function __construct(
        ViewHandlerInterface $viewHandler,
        TokenStorageInterface $tokenStorage,
        private FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        private DoctrineListBuilderFactoryInterface $listBuilderFactory,
        private RestHelperInterface $restHelper,
        private ContentManagerInterface $contentManager,
        private EntityManagerInterface $entityManager,
        private ExampleRepository $exampleRepository,
    ) {
        parent::__construct($viewHandler, $tokenStorage);
    }

    /**
     * The cgetAction looks like for a normal list action.
     */
    public function cgetAction(Request $request): Response
    {
        /** @var DoctrineFieldDescriptorInterface[] $fieldDescriptors */
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors(Example::RESOURCE_KEY);
        /** @var DoctrineListBuilder $listBuilder */
        $listBuilder = $this->listBuilderFactory->create(Example::class);
        $listBuilder->addSelectField($fieldDescriptors['locale']);

        if (isset($fieldDescriptors['ghostLocale'])) {
            $listBuilder->addSelectField($fieldDescriptors['ghostLocale']);
        }
        $listBuilder->setParameter('locale', $request->query->get('locale'));
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        $listRepresentation = new PaginatedRepresentation(
            $listBuilder->execute(),
            Example::RESOURCE_KEY,
            (int) $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            $listBuilder->count()
        );

        return $this->handleView($this->view($listRepresentation));
    }

    public function getVersionsAction(Request $request, string $id): Response
    {
        $locale = $request->query->get('locale');

        /** @var DoctrineFieldDescriptorInterface[] $fieldDescriptors */
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors('examples_versions');
        /** @var DoctrineListBuilder $listBuilder */
        $listBuilder = $this->listBuilderFactory->create(Example::class);
        $listBuilder->setParameter('locale', $locale);
        $listBuilder->setParameter('id', $id);
        $listBuilder->setIdField($fieldDescriptors['id']);
        $listBuilder->sort($fieldDescriptors['version'], 'DESC');
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        $result = $listBuilder->execute();
        $listRepresentation = new PaginatedRepresentation(
            $result,
            'examples_versions',
            (int) $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            $listBuilder->count(),
        );

        return $this->handleView($this->view($listRepresentation));
    }

    /**
     * The getAction loads the main entity and resolves its content based on the current dimensionAttributes.
     */
    public function getAction(Request $request, int $id): Response
    {
        $dimensionAttributes = $this->getDimensionAttributes($request);

        $example = $this->exampleRepository->findOneBy(
            ['id' => $id],
            [ExampleRepository::SELECT_EXAMPLE_CONTENT => ['dimensionAttributes' => $dimensionAttributes]]
        );

        if (!$example) {
            throw new NotFoundHttpException();
        }

        $dimensionContent = $this->contentManager->resolve($example, $dimensionAttributes);

        return $this->handleView($this->view($this->normalize($example, $dimensionContent)));
    }

    /**
     * The postAction creates the main entity and saves its content based on the current dimensionAttributes.
     */
    public function postAction(Request $request): Response
    {
        $example = new Example();

        $data = $this->getData($request);
        $dimensionAttributes = $this->getDimensionAttributes($request);

        $dimensionContent = $this->contentManager->persist($example, $data, $dimensionAttributes);

        $this->entityManager->persist($example);
        $this->entityManager->flush();

        if ('publish' === $request->query->get('action')) {
            $dimensionContent = $this->contentManager->applyTransition(
                $example,
                $dimensionAttributes,
                WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH
            );

            $this->entityManager->flush();
        }

        return $this->handleView($this->view($this->normalize($example, $dimensionContent), 201));
    }

    /**
     *  The postTriggerAction loads the main entity and applies transitions based on the given action parameter.
     */
    public function postTriggerAction(string $id, Request $request): Response
    {
        $dimensionAttributes = $this->getDimensionAttributes($request);
        $action = $request->query->get('action');

        // For workflow operations that copy/modify across locales or versions, load ALL dimension contents
        if (\in_array($action, ['copy_locale', 'restore', 'unpublish', 'remove_draft'], true)) {
            $example = $this->exampleRepository->findOneBy(
                ['id' => (int) $id],
                [ExampleRepository::SELECT_EXAMPLE_CONTENT => true]
            );
        } else {
            $example = $this->exampleRepository->findOneBy(
                ['id' => (int) $id],
                [ExampleRepository::SELECT_EXAMPLE_CONTENT => ['dimensionAttributes' => $dimensionAttributes]]
            );
        }

        if (!$example) {
            throw new NotFoundHttpException();
        }

        switch ($action) {
            case 'copy_locale':
                $dimensionContent = $this->contentManager->copy(
                    $example,
                    [
                        'stage' => DimensionContentInterface::STAGE_DRAFT,
                        'locale' => $request->query->get('src'),
                    ],
                    $example,
                    [
                        'stage' => DimensionContentInterface::STAGE_DRAFT,
                        'locale' => $request->query->get('dest'),
                    ]
                );

                $this->entityManager->flush();

                return $this->handleView($this->view($this->normalize($example, $dimensionContent)));
            case 'unpublish':
                $dimensionContent = $this->contentManager->applyTransition(
                    $example,
                    $dimensionAttributes,
                    WorkflowInterface::WORKFLOW_TRANSITION_UNPUBLISH
                );

                $this->entityManager->flush();

                return $this->handleView($this->view($this->normalize($example, $dimensionContent)));
            case 'remove_draft':
                $dimensionContent = $this->contentManager->applyTransition(
                    $example,
                    $dimensionAttributes,
                    WorkflowInterface::WORKFLOW_TRANSITION_REMOVE_DRAFT
                );

                $this->entityManager->flush();

                return $this->handleView($this->view($this->normalize($example, $dimensionContent)));
            case 'restore':
                $version = (int) $request->query->get('version');
                $dimensionContent = $this->contentManager->copy(
                    $example,
                    [
                        'stage' => $dimensionAttributes['stage'] ?? DimensionContentInterface::STAGE_DRAFT,
                        'locale' => $dimensionAttributes['locale'] ?? null,
                        'version' => $version,
                    ],
                    $example,
                    [
                        'stage' => $dimensionAttributes['stage'] ?? DimensionContentInterface::STAGE_DRAFT,
                        'locale' => $dimensionAttributes['locale'] ?? null,
                        'version' => DimensionContentInterface::CURRENT_VERSION,
                    ],
                    [
                        'ignoredAttributes' => ['url'],
                    ]
                );

                $this->entityManager->flush();

                return $this->handleView($this->view($this->normalize($example, $dimensionContent)));
            default:
                throw new RestException('Unrecognized action: ' . $action);
        }
    }

    /**
     * The putAction loads the main entity and saves its content based on the current dimensionAttributes.
     */
    public function putAction(Request $request, int $id): Response
    {
        $data = $this->getData($request);
        $dimensionAttributes = $this->getDimensionAttributes($request);

        // Load all dimension contents when publishing (workflow needs access to all locales/stages)
        if ('publish' === $request->query->get('action')) {
            $example = $this->exampleRepository->findOneBy(
                ['id' => $id],
                [ExampleRepository::SELECT_EXAMPLE_CONTENT => true]
            );
        } else {
            $example = $this->exampleRepository->findOneBy(
                ['id' => $id],
                [ExampleRepository::SELECT_EXAMPLE_CONTENT => ['dimensionAttributes' => $dimensionAttributes]]
            );
        }

        if (!$example) {
            throw new NotFoundHttpException();
        }

        /** @var ExampleDimensionContent $dimensionContent */
        $dimensionContent = $this->contentManager->persist($example, $data, $dimensionAttributes);
        if (WorkflowInterface::WORKFLOW_PLACE_PUBLISHED === $dimensionContent->getWorkflowPlace()) {
            $dimensionContent = $this->contentManager->applyTransition(
                $example,
                $dimensionAttributes,
                WorkflowInterface::WORKFLOW_TRANSITION_CREATE_DRAFT
            );
        }

        $this->entityManager->flush();

        if ('publish' === $request->query->get('action')) {
            $dimensionContent = $this->contentManager->applyTransition(
                $example,
                $dimensionAttributes,
                WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH
            );

            $this->entityManager->flush();
        }

        return $this->handleView($this->view($this->normalize($example, $dimensionContent)));
    }

    /**
     * The deleteAction handles the main entity through cascading also the content will be removed.
     */
    public function deleteAction(int $id): Response
    {
        /** @var Example $example */
        $example = $this->entityManager->getReference(Example::class, $id);

        $this->entityManager->remove($example);
        $this->entityManager->flush();

        return new Response('', 204);
    }

    /**
     * Will return e.g. ['locale' => 'en'].
     *
     * @return array<string, mixed>
     */
    protected function getDimensionAttributes(Request $request): array
    {
        return $request->query->all();
    }

    /**
     * Will return e.g. ['title' => 'Test', 'template' => 'example-2', ...].
     *
     * @return array<string, mixed>
     */
    protected function getData(Request $request): array
    {
        $data = $request->request->all();

        return $data;
    }

    /**
     * Resolve will convert the resolved DimensionContentInterface object into a normalized array.
     *
     * @return mixed[]
     */
    protected function normalize(Example $example, ExampleDimensionContent $dimensionContent): array
    {
        $normalizedContent = $this->contentManager->normalize($dimensionContent);

        return $normalizedContent;
    }

    /**
     * Get versions for an example.
     */
    public function versionsAction(Request $request, int $id): Response
    {
        /** @var Example|null $example */
        $example = $this->entityManager->getRepository(Example::class)->findOneBy(['id' => $id]);

        if (!$example) {
            throw new NotFoundHttpException();
        }

        $locale = $request->query->get('locale');
        /** @var FieldDescriptorInterface[] $fieldDescriptors */
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors('examples_versions');

        /** @var DoctrineListBuilder $listBuilder */
        $listBuilder = $this->listBuilderFactory->create(ExampleDimensionContent::class);
        $listBuilder->setParameter('resourceId', $id);
        $listBuilder->setParameter('locale', $locale);

        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        $listRepresentation = new PaginatedRepresentation(
            $listBuilder->execute(),
            'examples_versions',
            (int) $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            $listBuilder->count()
        );

        return $this->handleView($this->view($listRepresentation));
    }
}
