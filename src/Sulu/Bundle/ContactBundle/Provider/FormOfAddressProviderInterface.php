<?php

declare(strict_types=1);

namespace Sulu\Bundle\ContactBundle\Provider;

interface FormOfAddressProviderInterface
{
    /**
     * @return array<array{
     *     name: string,
     *     title: string,
     * }>
     */
    public function getValues(string $locale): array;
}
