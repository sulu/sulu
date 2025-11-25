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

namespace Sulu\Bundle\CategoryBundle\Trash;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\CategoryBundle\Admin\CategoryAdmin;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryRestoredEvent;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\KeywordInterface;
use Sulu\Bundle\CategoryBundle\Entity\KeywordRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Exception\CategoryKeyNotUniqueException;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\TrashBundle\Application\DoctrineRestoreHelper\DoctrineRestoreHelperInterface;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfiguration;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfigurationProviderInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RestoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\StoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Webmozart\Assert\Assert;

final class CategoryTrashItemHandler implements
    StoreTrashItemHandlerInterface,
    RestoreTrashItemHandlerInterface,
    RestoreConfigurationProviderInterface
{
    /**
     * @var TrashItemRepositoryInterface
     */
    private $trashItemRepository;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var CategoryTranslationRepositoryInterface
     */
    private $categoryTranslationRepository;

    /**
     * @var KeywordRepositoryInterface
     */
    private $keywordRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var DoctrineRestoreHelperInterface
     */
    private $doctrineRestoreHelper;

    /**
     * @var DomainEventCollectorInterface
     */
    private $domainEventCollector;

    public function __construct(
        TrashItemRepositoryInterface $trashItemRepository,
        CategoryRepositoryInterface $categoryRepository,
        CategoryTranslationRepositoryInterface $categoryTranslationRepository,
        KeywordRepositoryInterface $keywordRepository,
        EntityManagerInterface $entityManager,
        DoctrineRestoreHelperInterface $doctrineRestoreHelper,
        DomainEventCollectorInterface $domainEventCollector
    ) {
        $this->trashItemRepository = $trashItemRepository;
        $this->categoryRepository = $categoryRepository;
        $this->categoryTranslationRepository = $categoryTranslationRepository;
        $this->keywordRepository = $keywordRepository;
        $this->entityManager = $entityManager;
        $this->doctrineRestoreHelper = $doctrineRestoreHelper;
        $this->domainEventCollector = $domainEventCollector;
    }

    /**
     * @param CategoryInterface $category
     */
    public function store(object $category, array $options = []): TrashItemInterface
    {
        Assert::isInstanceOf($category, CategoryInterface::class);

        $parent = $category->getParent();
        $creator = $category->getCreator();

        $categoryTitles = [];
        $data = [
            'key' => $category->getKey(),
            'defaultLocale' => $category->getDefaultLocale(),
            'parentId' => $parent ? $parent->getId() : null,
            'created' => $category->getCreated()->format('c'),
            'creatorId' => $creator ? $creator->getId() : null,
            'translations' => [],
        ];

        /** @var CategoryTranslationInterface $translation */
        foreach ($category->getTranslations() as $translation) {
            $categoryTitles[$translation->getLocale()] = $translation->getTranslation();

            $creator = $translation->getCreator();

            $translationData = [
                'translation' => $translation->getTranslation(),
                'description' => $translation->getDescription(),
                'locale' => $translation->getLocale(),
                'created' => $translation->getCreated()->format('c'),
                'creatorId' => $creator ? $creator->getId() : null,
                'mediaIds' => [],
                'keywords' => [],
            ];

            foreach ($translation->getMedias() as $media) {
                $translationData['mediaIds'][] = $media->getId();
            }

            /** @var KeywordInterface $keyword */
            foreach ($translation->getKeywords() as $keyword) {
                $creator = $keyword->getCreator();

                $translationData['keywords'][] = [
                    'keyword' => $keyword->getKeyword(),
                    'created' => $keyword->getCreated()->format('c'),
                    'creatorId' => $creator ? $creator->getId() : null,
                ];
            }

            $data['translations'][] = $translationData;
        }

        return $this->trashItemRepository->create(
            CategoryInterface::RESOURCE_KEY,
            (string) $category->getId(),
            $categoryTitles,
            $data,
            null,
            $options,
            CategoryAdmin::SECURITY_CONTEXT,
            null,
            null
        );
    }

    public function restore(TrashItemInterface $trashItem, array $restoreFormData = []): object
    {
        $id = (int) $trashItem->getResourceId();
        $data = $trashItem->getRestoreData();
        $parentId = $restoreFormData['parentId'];

        if ($data['key'] && null !== $this->categoryRepository->findCategoryByKey($data['key'])) {
            throw new CategoryKeyNotUniqueException($data['key']);
        }

        $category = $this->categoryRepository->createNew();
        $category->setKey($data['key']);
        $category->setDefaultLocale($data['defaultLocale']);
        $category->setCreated(new \DateTimeImmutable($data['created']));
        $category->setCreator($this->findEntity(UserInterface::class, $data['creatorId']));

        if ($parentId) {
            $category->setParent($this->categoryRepository->findCategoryById($parentId));
        }

        foreach ($data['translations'] as $translationData) {
            $translation = $this->categoryTranslationRepository->createNew();
            $translation->setCategory($category);
            $category->addTranslation($translation);

            /** @var string $translationName */
            $translationName = $translationData['translation'];
            $translation->setTranslation($translationName);
            /** @var string|null $translationDescription */
            $translationDescription = $translationData['description'] ?? null;
            $translation->setDescription($translationDescription);
            $translation->setLocale($translationData['locale']);
            $translation->setCreated(new \DateTimeImmutable($translationData['created']));
            $translation->setCreator($this->findEntity(UserInterface::class, $translationData['creatorId']));

            $medias = [];
            foreach ($translationData['mediaIds'] as $mediaId) {
                if ($media = $this->findEntity(MediaInterface::class, $mediaId)) {
                    $medias[] = $media;
                }
            }
            $translation->setMedias($medias);

            foreach ($translationData['keywords'] as $keywordData) {
                $keyword = $this->keywordRepository->findByKeyword($keywordData['keyword'], $translationData['locale']);

                if (!$keyword) {
                    $keyword = $this->keywordRepository->createNew();
                    $this->entityManager->persist($keyword);

                    $keyword->setKeyword($keywordData['keyword']);
                    $keyword->setLocale($translationData['locale']);
                    $keyword->setCreated(new \DateTimeImmutable($keywordData['created']));
                    $keyword->setCreator($this->findEntity(UserInterface::class, $keywordData['creatorId']));
                }

                $keyword->addCategoryTranslation($translation);
                $translation->addKeyword($keyword);
            }
        }

        $this->domainEventCollector->collect(
            new CategoryRestoredEvent($category, $data)
        );

        if (null === $this->categoryRepository->findCategoryById($id)) {
            $this->doctrineRestoreHelper->persistAndFlushWithId($category, $id);
        } else {
            $this->entityManager->persist($category);
            $this->entityManager->flush();
        }

        return $category;
    }

    public static function getResourceKey(): string
    {
        return CategoryInterface::RESOURCE_KEY;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     * @param mixed|null $id
     *
     * @return T|null
     */
    private function findEntity(string $className, $id)
    {
        if ($id) {
            return $this->entityManager->find($className, $id);
        }

        return null;
    }

    public function getConfiguration(): RestoreConfiguration
    {
        return new RestoreConfiguration('restore_category', CategoryAdmin::EDIT_FORM_VIEW, ['id' => 'id']);
    }
}
