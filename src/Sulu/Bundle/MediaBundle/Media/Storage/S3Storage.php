<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Storage;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sulu\Bundle\MediaBundle\Media\Exception\S3DeleteException;
use Sulu\Bundle\MediaBundle\Media\Exception\S3UploadException;

/**
 * Class S3Storage
 * @package Sulu\Bundle\MediaBundle\Media\Storage
 */
class S3Storage implements StorageInterface
{
    /**
     * @var string
     */
    private $storageOption = null;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var S3Client
     */
    private $s3;

    /**
     * @var string $s3bucket
     */
    private $s3Bucket;

    /**
     * @var array
     */
    private $s3Config;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        $s3Config = array(),
        $logger = null
    ) {
        $this->s3Config = array(
            'key' => $s3Config['key'],
            'secret' => $s3Config['secret'],
            'region' => $s3Config['region'],
        );
        $this->s3Bucket = $s3Config['bucket'];
        $this->logger = $logger ? : new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function save($tempPath, $fileName, $version)
    {
        $this->storageOption = new \stdClass();

        $resource = fopen($tempPath, 'r');
        $mediaId = 'media-' . uniqid() . '-' . $fileName . '-' . $version;
        try {
            $s3 = S3Client::factory($this->s3Config);
            $model = $s3->upload($this->s3Bucket, $mediaId, $resource, 'public-read');

            $this->addStorageOption('id', $mediaId);
            $this->addStorageOption('bucket', $this->s3Bucket);
            $this->addStorageOption('region', $this->s3Config['region']);
            $this->addStorageOption('data', $model->getAll());
        } catch (S3Exception $e) {
            throw new S3UploadException('Error when upload file to S3: ' . $e->getMessage());
        }

        return json_encode($this->storageOption);
    }

    /**
     * {@inheritdoc}
     */
    public function load($fileName, $version, $storageOption)
    {
        $this->storageOption = json_decode($storageOption);

        $data = $this->getStorageOption('data');

        return $data->ObjectURL;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($storageOption)
    {
        $this->storageOption = json_decode($storageOption);

        $bucket = $this->getStorageOption('bucket');
        $region = $this->getStorageOption('region');
        $mediaId = $this->getStorageOption('id');

        try {
            $s3 = S3Client::factory(array_merge($this->s3Config, array(
                'region' => $region
            )));

            $return = $s3->deleteObject(array(
                'Bucket' => $bucket,
                'Key' => $mediaId
            ));
        } catch (S3Exception $e) {
            throw new S3DeleteException('Error when upload file to S3: ' . $e->getMessage());
        }

        return $return;
    }

    /**
     * @param $key
     * @param $value
     */
    private function addStorageOption($key, $value)
    {
        $this->storageOption->$key = $value;
    }

    /**
     * @param $key
     * @return mixed
     */
    private function getStorageOption($key)
    {
        return isset($this->storageOption->$key) ? $this->storageOption->$key : null;
    }
}
