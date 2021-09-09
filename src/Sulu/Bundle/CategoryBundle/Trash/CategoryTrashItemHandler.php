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
use Sulu\Bundle\CategoryBundle\Entity\CategoryMetaInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryMetaRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\KeywordInterface;
use Sulu\Bundle\CategoryBundle\Entity\KeywordRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Exception\CategoryKeyNotUniqueException;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\TrashBundle\Application\DoctrineRestoreHelper\DoctrineRestoreHelperInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RestoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\StoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Webmozart\Assert\Assert;

final class CategoryTrashItemHandler implements StoreTrashItemHandlerInterface, RestoreTrashItemHandlerInterface
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
     * @var CategoryMetaRepositoryInterface
     */
    private $categoryMetaRepository;

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
        CategoryMetaRepositoryInterface $categoryMetaRepository,
        CategoryTranslationRepositoryInterface $categoryTranslationRepository,
        KeywordRepositoryInterface $keywordRepository,
        EntityManagerInterface $entityManager,
        DoctrineRestoreHelperInterface $doctrineRestoreHelper,
        DomainEventCollectorInterface $domainEventCollector
    ) {
        $this->trashItemRepository = $trashItemRepository;
        $this->categoryRepository = $categoryRepository;
        $this->categoryMetaRepository = $categoryMetaRepository;
        $this->categoryTranslationRepository = $categoryTranslationRepository;
        $this->keywordRepository = $keywordRepository;
        $this->entityManager = $entityManager;
        $this->doctrineRestoreHelper = $doctrineRestoreHelper;
        $this->domainEventCollector = $domainEventCollector;
    }

    /**
     * @param CategoryInterface $category
     */
    public function store(object $category): TrashItemInterface
    {
        Assert::isInstanceOf($category, CategoryInterface::class);

        $parent = $category->getParent();
        $creator = $category->getCreator();

        $data = [
            'key' => $category->getKey(),
            'defaultLocale' => $category->getDefaultLocale(),
            'parentId' => $parent ? $parent->getId() : null,
            'created' => $category->getCreated()->format('c'),
            'creatorId' => $creator ? $creator->getId() : null,
            'metas' => [],
            'translations' => [],
        ];

        /** @var CategoryMetaInterface $meta */
        foreach ($category->getMeta() as $meta) {
            $metaData = [
                'key' => $meta->getKey(),
                'value' => $meta->getValue(),
                'locale' => $meta->getLocale(),
            ];

            $data['metas'][] = $metaData;
        }

        /** @var CategoryTranslationInterface $translation */
        foreach ($category->getTranslations() as $translation) {
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

            /** @var MediaInterface $media */
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

        $categoryTitles = [];
        foreach ($category->getTranslations() as $translation) {
            $categoryTitles[$translation->getLocale()] = $translation->getTranslation();
        }

        return $this->trashItemRepository->create(
            CategoryInterface::RESOURCE_KEY,
            (string) $category->getId(),
            $data,
            $categoryTitles,
            CategoryAdmin::SECURITY_CONTEXT,
            null,
            null
        );
    }

    public function restore(TrashItemInterface $trashItem, array $restoreFormData): object
    {
        $id = (int) $trashItem->getResourceId();
        $data = $trashItem->getRestoreData();
        $parentId = $restoreFormData['parent'];

        if ($data['key'] && null !== $this->categoryRepository->findCategoryByKey($data['key'])) {
            throw new CategoryKeyNotUniqueException($data['key']);
        }

        $category = $this->categoryRepository->createNew();
        $category->setKey($data['key']);
        $category->setDefaultLocale($data['defaultLocale']);
        $category->setCreated(new \DateTime($data['created']));

        if ($data['creatorId']) {
            $category->setCreator($this->entityManager->find(UserInterface::class, $data['creatorId']));
        }

        if ($parentId) {
            $category->setParent($this->categoryRepository->findCategoryById($parentId));
        }

        foreach ($data['metas'] as $metaData) {
            $meta = $this->categoryMetaRepository->createNew();
            $this->entityManager->persist($meta);

            $meta->setCategory($category);
            $meta->setKey($metaData['key']);
            $meta->setValue($metaData['value']);
            $meta->setLocale($metaData['locale']);

            $category->addMeta($meta);
        }

        foreach ($data['translations'] as $translationData) {
            $translation = $this->categoryTranslationRepository->createNew();
            $this->entityManager->persist($translation);

            $translation->setCategory($category);
            $translation->setTranslation($translationData['translation']);
            $translation->setDescription($translationData['description']);
            $translation->setLocale($translationData['locale']);
            $translation->setCreated(new \DateTime($translationData['created']));

            if ($translationData['creatorId']) {
                $translation->setCreator($this->entityManager->find(UserInterface::class, $translationData['creatorId']));
            }

            $medias = [];
            foreach ($translationData['mediaIds'] as $mediaId) {
                if ($media = $this->entityManager->find(MediaInterface::class, $mediaId)) {
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
                    $keyword->setCreated(new \DateTime($keywordData['created']));

                    if ($keywordData['creatorId']) {
                        $keyword->setCreator($this->entityManager->find(UserInterface::class, $keywordData['creatorId']));
                    }
                }

                $keyword->addCategoryTranslation($translation);
                $translation->addKeyword($keyword);
            }

            $category->addTranslation($translation);
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
}
