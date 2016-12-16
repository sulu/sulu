<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\FormatOptions;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FormatOptions;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\FileVersionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\FormatNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\FormatOptionsMissingParameterException;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;

/**
 * An implementation of a manager handling format options of a file version.
 */
class FormatOptionsManager implements FormatOptionsManagerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $formatOptionsRepository;

    /**
     * @var MediaManagerInterface
     */
    private $mediaManager;

    /**
     * @var FormatManagerInterface
     */
    private $formatManager;

    /**
     * @var array
     */
    private $formats;

    /**
     * @param EntityManagerInterface $em
     * @param EntityRepository $formatOptionsRepository
     * @param MediaManagerInterface $mediaManager
     * @param FormatManagerInterface $formatManager
     * @param array $formats
     */
    public function __construct(
        EntityManagerInterface $em,
        EntityRepository $formatOptionsRepository,
        MediaManagerInterface $mediaManager,
        FormatManagerInterface $formatManager,
        array $formats
    ) {
        $this->em = $em;
        $this->formatOptionsRepository = $formatOptionsRepository;
        $this->mediaManager = $mediaManager;
        $this->formatManager = $formatManager;
        $this->formats = $formats;
    }

    /**
     * {@inheritdoc}
     */
    public function get($mediaId, $formatKey)
    {
        if (!isset($this->formats[$formatKey])) {
            throw new FormatNotFoundException($formatKey);
        }

        $media = $this->mediaManager->getEntityById($mediaId);
        $fileVersion = $this->getFileVersionForMedia($media);

        /** @var FormatOptions $formatOptions */
        $formatOptions = $this->formatOptionsRepository->find(
            [
                'fileVersion' => $fileVersion,
                'formatKey' => $formatKey,
            ]
        );

        if (!isset($formatOptions)) {
            return [];
        }

        return $this->entityToArray($formatOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function getAll($mediaId)
    {
        $media = $this->mediaManager->getEntityById($mediaId);
        $fileVersion = $this->getFileVersionForMedia($media);

        $formatOptions = $this->formatOptionsRepository->findBy(['fileVersion' => $fileVersion]);

        $formatOptionsArray = [];

        /** @var FormatOptions $formatOptionEntity */
        foreach ($formatOptions as $formatOptionEntity) {
            $formatOptionsArray[$formatOptionEntity->getFormatKey()] = $this->entityToArray($formatOptionEntity);
        }

        return $formatOptionsArray;
    }

    /**
     * {@inheritdoc}
     */
    public function save($mediaId, $formatKey, array $data)
    {
        if (!isset($this->formats[$formatKey])) {
            throw new FormatNotFoundException($formatKey);
        }

        $media = $this->mediaManager->getEntityById($mediaId);
        $fileVersion = $this->getFileVersionForMedia($media);

        $formatOptions = $fileVersion->getFormatOptions()->get($formatKey);
        if (!isset($formatOptions)) {
            $formatOptions = new FormatOptions();
            $formatOptions->setFileVersion($fileVersion);
            $fileVersion->addFormatOptions($formatOptions);
            $formatOptions->setFormatKey($formatKey);
        }

        $formatOptions = $this->setDataOnEntity($formatOptions, $data);
        $fileVersion->increaseSubVersion();

        $this->em->persist($formatOptions);
        $this->em->persist($fileVersion);

        $this->purgeMedia($mediaId, $fileVersion);

        return $formatOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($mediaId, $formatKey)
    {
        if (!isset($this->formats[$formatKey])) {
            throw new FormatNotFoundException($formatKey);
        }

        $media = $this->mediaManager->getEntityById($mediaId);
        $fileVersion = $this->getFileVersionForMedia($media);

        $formatOptions = $fileVersion->getFormatOptions()->get($formatKey);
        if (isset($formatOptions)) {
            $fileVersion->getFormatOptions()->remove($formatKey);
            $fileVersion->increaseSubVersion();
            $this->em->remove($formatOptions);
            $this->em->persist($fileVersion);
            $this->purgeMedia($mediaId, $fileVersion);
        }
    }

    /**
     * Gets the latest file-version of a given media.
     *
     * @param MediaInterface $media
     *
     * @throws FileVersionNotFoundException
     *
     * @return FileVersion
     */
    private function getFileVersionForMedia(MediaInterface $media)
    {
        /** @var File $file */
        $file = $media->getFiles()->get(0);
        if (!isset($file)) {
            throw new FileVersionNotFoundException($media->getId(), 'latest');
        }

        $fileVersion = $file->getLatestFileVersion();
        if (!isset($fileVersion)) {
            throw new FileVersionNotFoundException($media->getId(), 'latest');
        }

        return $fileVersion;
    }

    /**
     * Sets a given array of data onto a given format-options entity.
     *
     * @param FormatOptions $formatOptions
     * @param array $data
     *
     * @throws FormatOptionsMissingParameterException
     *
     * @return FormatOptions The format-options entity with set data
     */
    private function setDataOnEntity(FormatOptions $formatOptions, array $data)
    {
        if (!isset($data['cropX']) || !isset($data['cropY']) || !isset($data['cropWidth']) || !isset($data['cropHeight'])) {
            throw new FormatOptionsMissingParameterException();
        }

        $formatOptions->setCropX($data['cropX']);
        $formatOptions->setCropY($data['cropY']);
        $formatOptions->setCropWidth($data['cropWidth']);
        $formatOptions->setCropHeight($data['cropHeight']);

        return $formatOptions;
    }

    /**
     * Converts a given entity to its array representation.
     *
     * @param FormatOptions $formatOptions
     *
     * @return array
     */
    private function entityToArray(FormatOptions $formatOptions)
    {
        return [
            'cropX' => $formatOptions->getCropX(),
            'cropY' => $formatOptions->getCropY(),
            'cropWidth' => $formatOptions->getCropWidth(),
            'cropHeight' => $formatOptions->getCropHeight(),
        ];
    }

    /**
     * Purges a file-version of a media with a given id.
     *
     * @param int $mediaId
     * @param FileVersion $fileVersion
     */
    private function purgeMedia($mediaId, FileVersion $fileVersion)
    {
        $this->formatManager->purge(
            $mediaId,
            $fileVersion->getName(),
            $fileVersion->getMimeType(),
            $fileVersion->getStorageOptions()
        );
    }
}
