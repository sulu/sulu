<?php

namespace Sulu\Bundle\SearchBundle\Search;

use Massive\Bundle\SearchBundle\Search\Document as BaseDocument;
use Sulu\Bundle\SearchBundle\Search\Document;

class Document extends BaseDocument
{
    protected $thumbnailIds = array();

    public function setThumbnailIds($thumbnailIds)
    {
        $this->thumbnailId = $thumbnailIds;
    }

    public function getThumbnailIds()
    {
        return $this->getThumbnailIds();
    }

    public function getThumbnailId()
    {
        return current($this->thumbnailIds);
    }
}
