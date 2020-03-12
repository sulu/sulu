<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Content\Types;

use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\ContentTypeExportInterface;
use Sulu\Component\Content\SimpleContentType;

class SingleCategorySelection extends SimpleContentType implements ContentTypeExportInterface
{
    /**
     * Responsible for persisting the categories in the database.
     *
     * @var CategoryManagerInterface
     */
    private $categoryManager;

    public function __construct(CategoryManagerInterface $categoryManager)
    {
        parent::__construct('single_category_selection');

        $this->categoryManager = $categoryManager;
    }

    public function getContentData(PropertyInterface $property)
    {
        $id = $property->getValue();
        if (!$id) {
            return null;
        }

        $entity = $this->categoryManager->findById($id);
        $category = $this->categoryManager->getApiObject($entity, $property->getStructure()->getLanguageCode());

        return $category->toArray();
    }
}
