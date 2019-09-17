<?php

namespace Sulu\Component\Rest\ListBuilder;

use JMS\Serializer\Annotation as Serializer;

/**
 * This class represents a list for our common rest services.
 */
class PaginatedRepresentation extends CollectionRepresentation
{
    /**
     * @Serializer\Expose()
     *
     * @var int
     */
    protected $page;

    /**
     * @Serializer\Expose()
     *
     * @var int
     */
    protected $limit;

    /**
     * @Serializer\Expose()
     *
     * @var int
     */
    protected $total;

    /**
     * @Serializer\Expose()
     *
     * @var int
     */
    protected $pages;

    public function __construct($data, string $rel, ?int $page, int $limit, int $total)
    {
        parent::__construct($data, $rel);
        $this->page = $page;
        $this->limit = $limit;
        $this->total = $total;
        $this->pages = ($limit ? ceil($total / $limit) : 1);
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPages(): int
    {
        return $this->pages;
    }
}
