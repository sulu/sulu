<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Category;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryKeywordAddedEvent;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryKeywordModifiedEvent;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryKeywordRemovedEvent;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\KeywordInterface;
use Sulu\Bundle\CategoryBundle\Entity\KeywordRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Exception\KeywordIsMultipleReferencedException;
use Sulu\Bundle\CategoryBundle\Exception\KeywordNotUniqueException;

/**
 * Manages keyword for categories.
 */
class KeywordManager implements KeywordManagerInterface
{
    public function __construct(
        private KeywordRepositoryInterface $keywordRepository,
        private CategoryTranslationRepositoryInterface $categoryTranslationRepository,
        private EntityManagerInterface $entityManager,
        private DomainEventCollectorInterface $domainEventCollector
    ) {
    }

    public function save(KeywordInterface $keyword, CategoryInterface $category, $force = null)
    {
        // overwrite existing keyword if force is present
        if (null === $force
            && $keyword->isReferencedMultiple()
            && \in_array($force, [self::FORCE_OVERWRITE, self::FORCE_DETACH, self::FORCE_MERGE, null])
        ) {
            // return conflict if keyword is used by other categories
            throw new KeywordIsMultipleReferencedException($keyword);
        }

        if (null !== $keyword->getId()
            && self::FORCE_MERGE !== $force
            && null !== $this->keywordRepository->findByKeyword($keyword->getKeyword(), $keyword->getLocale())
        ) {
            throw new KeywordNotUniqueException($keyword);
        }

        if (self::FORCE_DETACH === $force || self::FORCE_MERGE === $force) {
            $keyword = $this->handleDetach($keyword, $category);
        } else {
            $keyword = $this->handleOverwrite($keyword, $category);
        }

        /** @var CategoryTranslationInterface $categoryTranslation */
        foreach ($keyword->getCategoryTranslations() as $categoryTranslation) {
            $event = $keyword->getId()
                ? new CategoryKeywordModifiedEvent($categoryTranslation->getCategory(), $keyword)
                : new CategoryKeywordAddedEvent($categoryTranslation->getCategory(), $keyword);

            $this->domainEventCollector->collect($event);
        }

        return $keyword;
    }

    /**
     * Overwrites given keyword.
     *
     * @return KeywordInterface
     */
    private function handleOverwrite(KeywordInterface $keyword, CategoryInterface $category)
    {
        if (null !== $synonym = $this->findSynonym($keyword)) {
            // reset entity and remove it from category
            if ($this->entityManager->contains($keyword)) {
                $this->entityManager->refresh($keyword);
            }
            $this->delete($keyword, $category);

            // link this synonym to the category
            $keyword = $synonym;
        }

        $categoryTranslation = $category->findTranslationByLocale($keyword->getLocale());
        if (!$categoryTranslation) {
            $categoryTranslation = $this->createTranslation($category, $keyword->getLocale());
        }

        // if keyword already exists in category
        if ($categoryTranslation->hasKeyword($keyword)) {
            return $keyword;
        }

        $keyword->addCategoryTranslation($categoryTranslation);
        $categoryTranslation->addKeyword($keyword);

        // FIXME category and meta will not be updated if only keyword was changed
        $category->setChanged(new \DateTime());
        $categoryTranslation->setChanged(new \DateTime());

        return $keyword;
    }

    /**
     * Detach given and create new keyword entity.
     *
     * @return KeywordInterface
     */
    private function handleDetach(KeywordInterface $keyword, CategoryInterface $category)
    {
        $keywordString = $keyword->getKeyword();
        $keywordLocale = $keyword->getLocale();

        // if keyword wont be deleted (because of multiple references)
        // refresh it to be sure hat changes wont be written to
        // database.
        $this->entityManager->refresh($keyword);

        // delete old keyword from category
        $this->delete($keyword, $category);

        // create new keyword
        $newEntity = $this->keywordRepository->createNew();
        $newEntity->setKeyword($keywordString);
        $newEntity->setLocale($keywordLocale);

        // add new keyword to category
        return $this->save($newEntity, $category);
    }

    public function delete(KeywordInterface $keyword, CategoryInterface $category)
    {
        $categoryTranslation = $category->findTranslationByLocale($keyword->getLocale());

        if ($categoryTranslation) {
            $keyword->removeCategoryTranslation($categoryTranslation);
            $categoryTranslation->removeKeyword($keyword);

            // FIXME category and meta will not be updated if only keyword was changed
            $category->setChanged(new \DateTime());
            $categoryTranslation->setChanged(new \DateTime());

            // dispatch event only if keyword was flushed and therefore has an id
            if ($keyword->getId()) {
                $this->domainEventCollector->collect(new CategoryKeywordRemovedEvent(
                    $category,
                    $keyword->getLocale(),
                    $keyword->getId(),
                    $keyword->getKeyword()
                ));
            }
        }

        if ($keyword->isReferenced()) {
            return false;
        }

        $this->entityManager->remove($keyword);

        return true;
    }

    /**
     * Find the same keyword in the database or returns null if no synonym exists.
     *
     * @return KeywordInterface|null
     */
    private function findSynonym(KeywordInterface $keyword)
    {
        return $this->keywordRepository->findByKeyword($keyword->getKeyword(), $keyword->getLocale());
    }

    /**
     * Creates a new category translation for a given category and locale.
     *
     * @param string $locale
     *
     * @return CategoryTranslationInterface
     */
    private function createTranslation(CategoryInterface $category, $locale)
    {
        $categoryTranslation = $this->categoryTranslationRepository->createNew();
        $categoryTranslation->setLocale($locale);
        $categoryTranslation->setTranslation('');
        $categoryTranslation->setCategory($category);
        $category->addTranslation($categoryTranslation);

        $this->entityManager->persist($categoryTranslation);

        return $categoryTranslation;
    }
}
