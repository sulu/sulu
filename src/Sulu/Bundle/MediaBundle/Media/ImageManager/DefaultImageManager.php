<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ImageManager;

use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\MediaRepository;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyMediaIdNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyMediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\ImageConverterInterface;
use Sulu\Bundle\MediaBundle\Media\CacheStorage\CacheStorageInterface;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;

class DefaultImageManager implements ImageManagerInterface {

    /**
     * The repository for communication with the database
     * @var MediaRepository
     */
    private $mediaRepository;

    /**
     * @var ImageStorageInterface
     */
    private $imageStorage;

    /**
     * @var StorageInterface
     */
    private $originalStorage;

    /**
     * @var ImageConverterInterface
     */
    private $converter;

    /**
     * @param MediaRepository $mediaRepository
     * @param StorageInterface $originalStorage
     * @param CacheStorageInterface $imageStorage
     * @param ImageConverterInterface $converter
     */
    public function __construct(
        MediaRepository $mediaRepository,
        StorageInterface $originalStorage,
        CacheStorageInterface $imageStorage,
        ImageConverterInterface $converter
    )
    {
        $this->mediaRepository = $mediaRepository;
        $this->originalStorage = $originalStorage;
        $this->imageStorage = $imageStorage;
        $this->converter = $converter;
    }

    /**
     * {@inheritdoc}
     */
    public function returnImage($id, $format)
    {
        $uri = $this->getOriginal($id);
        $original = $this->createTmpOriginalFile($uri);

        $image = $this->converter->convert($original, $format);

        // only test
        header('Content-Type: image/png');
        echo $image->get('png');
        exit;
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaProperties($url)
    {
        return $this->imageStorage->analyzedMediaUrl($url);
    }

    /**
     * create a local temp file for the original
     * @param $uri
     * @return string
     */
    protected function createTmpOriginalFile($uri)
    {
        $tempFile = tempnam('/tmp', 'media_original');
        $handle = fopen($tempFile, 'w');
        fwrite($handle, file_get_contents($uri));
        fclose($handle);

        return $tempFile;
    }

    /**
     * @param $id
     * @return mixed
     * @throws ImageProxyMediaNotFoundException
     */
    protected function getOriginal($id)
    {
        $media = $this->mediaRepository->findMediaById($id);

        if (!$media) {
            throw new ImageProxyMediaNotFoundException('Media was not found');
        }

        $fileName = null;
        $storageOptions = null;

        /**
         * @var File $file
         */
        foreach ($media->getFiles() as $file) {
            $version = $file->getVersion();
            /**
             * @var FileVersion $fileVersion
             */
            foreach ($file->getFileVersions() as $fileVersion) {
                if ($fileVersion->getVersion() == $version) {
                    $fileName = $fileVersion->getName();
                    $storageOptions = $fileVersion->getStorageOptions();
                    break;
                }
            }
            break;
        }

        if (!$fileName) {
            throw new ImageProxyMediaNotFoundException('Media file version was not found');
        }

        return $this->originalStorage->load($fileName, $version, $storageOptions);
    }

} 
