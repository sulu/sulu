<?php

namespace Sulu\Bundle\MediaBundle\Media\Service;

use Sulu\Bundle\MediaBundle\Api\Media;
use Guzzle\Http\Client;

class ExternalService implements ServiceInterface
{
    protected $externalService = array();

    protected $serializer;

    protected $logger;

    protected $client;

    /**
     * @param array
     */
    public function __construct(
        $externalService,
        $serializer,
        $logger
    ) {
        $this->externalService = $externalService;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->client = new Client();
    }

    private function makeRequest($JSONstring, $action, $HTTPmethod)
    {
        foreach ($this->externalService as $key => $value) {
        	try 
        	{
            	$request = $this->client->$HTTPmethod($value[$action]);
            	$request->setBody($JSONstring, 'application/json');
            	$res = $request->send();
        	} catch (Guzzle\Http\Exception\BadResponseException $e) 
        	{
        		$this->logger->error('External Service Notification send error', $e->getResponse->getStatusCode);
        	}
        }
    }

    public function add(Media $media)
    {
        $mediaJson = $this->serializer->serialize($media, 'json');
        $this->makeRequest($mediaJson, 'add', 'post');
    }

    public function update(Media $media)
    {
    	$mediaJson = $this->serializer->serialize($media, 'json');
        $this->makeRequest($mediaJson, 'update', 'put');
    }

    public function delete(Media $media)
    {
        $mediaJson = $this->serializer->serialize($media, 'json');
        $this->makeRequest($mediaJson, 'delete', 'delete');
    }
}
