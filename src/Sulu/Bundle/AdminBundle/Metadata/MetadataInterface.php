<?php


namespace Sulu\Bundle\AdminBundle\Metadata;


interface MetadataInterface
{
    public function isCacheable(): bool;
}
