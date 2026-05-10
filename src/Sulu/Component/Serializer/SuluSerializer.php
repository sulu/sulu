<?php

namespace Sulu\Component\Serializer;

use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SuluSerializer implements SuluSerializerInterface
{
    public function __construct(private NormalizerInterface $normalizer) {}

    /**
     * @param object $data
     * @param array $groups
     *
     * @return array
     */
    public function toArray(mixed $data, array $groups = []): mixed
    {
        if (is_array($data)) {
            if (isset($data['field']) && !isset($data['errors'])) {
                $data['errors'] = [$data['field'] => $data['message']];
            }

            return $data;
        }

        $context = !empty($groups) ? ['groups' => $groups] : [];

        if (is_scalar($data) || null === $data) {
            return $data;
        }

        return $this->normalizer->normalize($data, null, $context);
    }

    public function handleView(mixed $data, array $groups = [], int $statusCode = 200): Response
    {
        if (204 === $statusCode) {
            return new Response(null, 204);
        }

        if ($data instanceof PaginatedRepresentation) {
            $paginatedList = [
                '_embedded' => [
                    $data->getRel() => $this->normalizer->normalize($data->getData(), null, $groups),
                ],
                'total' => $data->getTotal(),
                'page' => $data->getPage(),
                'pages' => $data->getPages(),
                'limit' => $data->getLimit(),
            ];

            return new JsonResponse($paginatedList);
        }

        $data = $this->toArray($data, $groups);
        return new JsonResponse($data, status: $statusCode);
    }
}
