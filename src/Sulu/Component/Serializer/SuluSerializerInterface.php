<?php

namespace Sulu\Component\Serializer;

use Symfony\Component\HttpFoundation\Response;

interface SuluSerializerInterface
{
    /**
     * @param object $data
     * @param array $groups
     *
     * @return array
     */
    public function toArray(mixed $data, array $groups = []): mixed;

    public function handleView(mixed $data, array $groups = [], int $statusCode = 200): Response;
}
