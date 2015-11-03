<?php

namespace Sulu\Component\Media\SmartContent;

use JMS\Serializer\SerializerInterface;
use Sulu\Bundle\MediaBundle\Api\Media;
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

    protected function getOptions(
        array $propertyParameter,
        array $options = []
    )
    {
        $request = $this->requestStack->getCurrentRequest();

         return array_filter([
             'filetype' => $request->get('filetype'),
             'language' => $request->get('lang')
         ]);
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