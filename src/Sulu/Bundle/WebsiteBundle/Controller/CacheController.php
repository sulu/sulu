<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Handles http cache actions.
 */
class CacheController extends Controller
{
    public function clearAction()
    {
        /** @var Filesystem $filesystem */
        $filesystem = $this->get('filesystem');

        /** @var Kernel $kernel */
        $kernel = $this->get('kernel');
        $kernelPath = $kernel->getRootDir();
        $kernelEnvironment = $kernel->getEnvironment();
        $path = sprintf('%s/cache/website/%s/http_cache', $kernelPath, $kernelEnvironment);

        if ($filesystem->exists($path)) {
            $filesystem->remove($path);
        }

        return new JsonResponse(array(), 200);
    }
}
