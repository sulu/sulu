<?php

namespace Sulu\Component\Media\SmartContent;

use JMS\Serializer\SerializerInterface;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Component\SmartContent\Orm\BaseDataProvider;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;

/**
 * Media DataProvider for SmartContent.
 */
class MediaDataProvider extends BaseDataProvider
{
    public function __construct(DataProviderRepositoryInterface $repository, SerializerInterface $serializer)
    {
        parent::__construct($repository, $serializer);

        $this->configuration = $this->initConfiguration(true, true, true, true, true, []);
    }

    /**
     * @param array $data
     * @return array
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