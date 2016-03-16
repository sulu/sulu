<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Category;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\CategoryBundle\Category\Exception\KeywordIsMultipleReferencedException;
use Sulu\Bundle\CategoryBundle\Category\Exception\KeywordNotUniqueException;
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\CategoryBundle\Entity\Keyword;

/**
 * Manages keyword for categories.
 */
class KeywordManager implements KeywordManagerInterface
{
    /**
     * @var KeywordRepositoryInterface
     */
    private $keywordRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(KeywordRepositoryInterface $keywordRepository, EntityManagerInterface $entityManager)
    {
        $this->keywordRepository = $keywordRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function save(Keyword $keyword, Category $category, $force = null)
    {
        // overwrite existing keyword if force is present
        if (null === $force
            && $keyword->isReferencedMultiple()
            && in_array($force, [self::FORCE_OVERWRITE, self::FORCE_DETACH, self::FORCE_MERGE, null])
        ) {
            // return conflict if keyword is used by other categories
            throw new KeywordIsMultipleReferencedException($keyword);
        }

        if ($keyword->getId() !== null
            && $force !== self::FORCE_MERGE
            && $this->keywordRepository->findByKeyword($keyword->getKeyword(), $keyword->getLocale()) !== null
        ) {
            throw new KeywordNotUniqueException($keyword);
        }

        if ($force === self::FORCE_DETACH || $force === self::FORCE_MERGE) {
            return $this->handleDetach($keyword, $category);
        }

        return $this->handleOverwrite($keyword, $category);
    }

    /**
     * Overwrites given keyword.
     *
     * @param Category $category
     * @param Keyword $keyword
     *
     * @return Keyword
     */
    private function handleOverwrite(Keyword $keyword, Category $category)
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
     * @param Category $category
     * @param Keyword $keyword
     *
     * @return Keyword
     */
    private function handleDetach(Keyword $keyword, Category $category)
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

    /**
     * {@inheritdoc}
     */
    public function delete(Keyword $keyword, Category $category)
    {
        $categoryTranslation = $category->findTranslationByLocale($keyword->getLocale());

        $keyword->removeCategoryTranslation($categoryTranslation);
        $categoryTranslation->removeKeyword($keyword);

        // FIXME category and meta will not be updated if only keyword was changed
        $category->setChanged(new \DateTime());
        $categoryTranslation->setChanged(new \DateTime());

        if ($keyword->isReferenced()) {
            return false;
        }

        $this->entityManager->remove($keyword);

        return true;
    }

    /**
     * Find the same keyword in the database or returns null if no synonym exists.
     *
     * @param Keyword $keyword
     *
     * @return Keyword|null
     */
    private function findSynonym(Keyword $keyword)
    {
        return $this->keywordRepository->findByKeyword($keyword->getKeyword(), $keyword->getLocale());
    }
}
