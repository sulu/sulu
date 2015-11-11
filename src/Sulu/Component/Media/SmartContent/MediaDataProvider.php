<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Media\SmartContent;

use JMS\Serializer\SerializerInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\SmartContent\Orm\BaseDataProvider;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Media DataProvider for SmartContent.
 */
class MediaDataProvider extends BaseDataProvider
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(DataProviderRepositoryInterface $repository, SerializerInterface $serializer, RequestStack $requestStack)
    {
        parent::__construct($repository, $serializer);

        $this->configuration = $this->initConfiguration(true, true, true, true, true, []);
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultPropertyParameter()
    {
        return [
            'mimetype_parameter' => new PropertyParameter('mimetype_parameter', 'mimetype', 'string'),
            'type_parameter' => new PropertyParameter('type_parameter', 'type', 'string'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getOptions(
        array $propertyParameter,
        array $options = []
    ) {
        $request = $this->requestStack->getCurrentRequest();

        return array_filter([
            'mimetype' => $request->get($propertyParameter['mimetype_parameter']->getValue()),
            'type' => $request->get($propertyParameter['types_parameter']->getValue()),
         ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function decorateDataItems(array $data)
    {
        return array_map(
            function ($item) {
                return new MediaDataItem($item);
            },
            $data
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getSerializationContext()
    {
        return parent::getSerializationContext()->setGroups(['fullMedia']);
    }
}
