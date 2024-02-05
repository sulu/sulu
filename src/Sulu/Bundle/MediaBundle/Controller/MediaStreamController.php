<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Controller;

use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\DispositionType\DispositionTypeResolver;
use Sulu\Bundle\MediaBundle\Media\Exception\FileVersionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaException;
use Sulu\Bundle\MediaBundle\Media\FormatCache\FormatCacheInterface;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Sulu\Component\PHPCR\PathCleanupInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MediaStreamController
{
    /**
     * @var FormatManagerInterface
     */
    protected $formatManager;

    /**
     * @var FormatCacheInterface
     */
    protected $formatCache;

    /**
     * @var MediaManagerInterface
     */
    protected $mediaManager;

    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var DispositionTypeResolver
     */
    protected $dispositionTypeResolver;

    /**
     * @var MediaRepositoryInterface
     */
    protected $mediaRepository;

    /**
     * @var PathCleanupInterface
     */
    protected $pathCleaner;

    /**
     * @var SecurityCheckerInterface|null
     */
    protected $securityChecker;

    public function __construct(
        DispositionTypeResolver $dispositionTypeResolver,
        MediaRepositoryInterface $mediaRepository,
        PathCleanupInterface $pathCleaner,
        FormatManagerInterface $formatManager,
        FormatCacheInterface $formatCache,
        MediaManagerInterface $mediaManager,
        StorageInterface $storage,
        ?SecurityCheckerInterface $securityChecker = null
    ) {
        $this->dispositionTypeResolver = $dispositionTypeResolver;
        $this->mediaRepository = $mediaRepository;
        $this->pathCleaner = $pathCleaner;
        $this->formatManager = $formatManager;
        $this->formatCache = $formatCache;
        $this->mediaManager = $mediaManager;
        $this->storage = $storage;
        $this->securityChecker = $securityChecker;
    }

    /**
     * @return Response
     */
    public function getImageAction(Request $request)
    {
        try {
            if (\ob_get_length()) {
                \ob_end_clean();
            }

            $url = $request->getPathInfo();

            $mediaProperties = $this->formatCache->analyzedMediaUrl($url);

            return $this->formatManager->returnImage(
                $mediaProperties['id'],
                $mediaProperties['format'],
                $mediaProperties['fileName']
            );
        } catch (ImageProxyException $e) {
            throw new NotFoundHttpException('Image create error. Code: ' . $e->getCode());
        }
    }

    /**
     * @param int $id
     *
     * @return Response
     */
    public function downloadAction(Request $request, $id)
    {
        try {
            if (\ob_get_length()) {
                \ob_end_clean();
            }

            $version = $request->get('v', null);
            $noCount = $request->get('no-count', false);

            $fileVersion = $this->getFileVersion($id, $version);

            if (!$fileVersion) {
                return new Response(null, 404);
            }

            if ($this->securityChecker) {
                $this->securityChecker->checkPermission(
                    new SecurityCondition(
                        MediaAdmin::SECURITY_CONTEXT,
                        null,
                        Collection::class,
                        $fileVersion->getFile()->getMedia()->getCollection()->getId()
                    ),
                    PermissionTypes::VIEW
                );
            }

            if ($request->query->has('inline')) {
                $forceInline = (bool) $request->get('inline', false);
                $dispositionType = $forceInline ? ResponseHeaderBag::DISPOSITION_INLINE : ResponseHeaderBag::DISPOSITION_ATTACHMENT;
            } else {
                $dispositionType = $this->dispositionTypeResolver->getByMimeType($fileVersion->getMimeType());
            }

            if (!$noCount) {
                $this->mediaManager->increaseDownloadCounter($fileVersion->getId());
            }

            $response = $this->getFileResponse($fileVersion, $request->getLocale(), $dispositionType);

            return $response;
        } catch (MediaException $e) {
            throw new NotFoundHttpException('File not found: ' . $e->getCode() . ' ' . $e->getMessage());
        }
    }

    protected function getFileResponse(
        FileVersion $fileVersion,
        string $locale,
        string $dispositionType = ResponseHeaderBag::DISPOSITION_ATTACHMENT
    ): Response {
        $storageOptions = $fileVersion->getStorageOptions();

        $storageType = $this->storage->getType($storageOptions);

        if (StorageInterface::TYPE_REMOTE === $storageType) {
            $response = new RedirectResponse($this->storage->getPath($storageOptions), 302);
            $response->setPrivate();

            return $response;
        } elseif (StorageInterface::TYPE_LOCAL === $storageType) {
            return $this->createBinaryFileResponse($fileVersion, $this->storage, $locale, $dispositionType);
        }

        throw new \RuntimeException(\sprintf('Storage type "%s" not supported.', $storageType));
    }

    private function createBinaryFileResponse(
        FileVersion $fileVersion,
        StorageInterface $storage,
        string $locale,
        string $dispositionType
    ): BinaryFileResponse {
        $fileName = $fileVersion->getName();
        $fileSize = $fileVersion->getSize();
        $storageOptions = $fileVersion->getStorageOptions();
        $mimeType = $fileVersion->getMimeType();
        $lastModified = $fileVersion->getCreated(); // use created as file itself is not changed when entity is changed

        $response = new BinaryFileResponse($storage->getPath($storageOptions));

        // Prepare headers
        $disposition = $response->headers->makeDisposition(
            $dispositionType,
            $fileName,
            $this->cleanUpFileName($fileName, $locale, $fileVersion->getExtension())
        );

        // Set headers for
        $file = $fileVersion->getFile();
        if ($fileVersion->getVersion() !== $file->getVersion()) {
            $latestFileVersion = $file->getLatestFileVersion();

            $response->headers->set(
                'Link',
                \sprintf(
                    '<%s>; rel="canonical"',
                    $this->mediaManager->getUrl(
                        $file->getMedia()->getId(),
                        $latestFileVersion->getName(),
                        $latestFileVersion->getVersion()
                    )
                )
            );
            $response->headers->set('X-Robots-Tag', 'noindex, follow');
        }

        // Set headers
        $response->headers->set('Content-Type', !empty($mimeType) ? $mimeType : 'application/octet-stream');
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-length', $fileSize);
        $response->headers->set('Last-Modified', $lastModified->format('D, d M Y H:i:s \G\M\T'));

        return $response;
    }

    /**
     * @param int $id
     * @param int $version
     *
     * @return FileVersion|null
     *
     * @throws FileVersionNotFoundException
     */
    protected function getFileVersion($id, $version)
    {
        /** @var MediaInterface $mediaEntity */
        $mediaEntity = $this->mediaRepository->findMediaByIdForRendering($id, null);

        if (!$mediaEntity) {
            return null;
        }

        $file = $mediaEntity->getFiles()[0];

        if (!$file) {
            return null;
        }

        if (!$version) {
            $version = $mediaEntity->getFiles()[0]->getVersion();
        }

        $fileVersion = $file->getFileVersion((int) $version);

        if (!$fileVersion) {
            throw new FileVersionNotFoundException($id, $version);
        }

        return $fileVersion;
    }

    /**
     * Cleaned up filename.
     *
     * @param string $fileName
     * @param string $locale
     * @param string $extension
     *
     * @return string
     */
    private function cleanUpFileName($fileName, $locale, $extension)
    {
        $pathInfo = \pathinfo($fileName);
        $cleanedFileName = $this->pathCleaner->cleanup($pathInfo['filename'], $locale);
        if ($extension) {
            $cleanedFileName .= '.' . $extension;
        }

        return $cleanedFileName;
    }
}
