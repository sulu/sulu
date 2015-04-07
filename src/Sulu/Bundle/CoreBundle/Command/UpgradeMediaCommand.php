<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Component\Content\Structure;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Upgrades media to 0.17.0
 * @deprecated
 */
class UpgradeMediaCommand extends ContainerAwareCommand
{
    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('sulu:upgrade:0.17.0:media')->setDescription('Upgrades media to 0.17.0');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->doctrine = $this->getContainer()->get('doctrine');
        $repository = $this->doctrine->getRepository('SuluMediaBundle:Media');

        foreach ($repository->findAll() as $media) {
            $this->upgradeMedia($media);
        }

        $this->doctrine->getManager()->flush();
    }

    private function upgradeMedia(Media $media)
    {
        foreach ($media->getFiles() as $file) {
            $this->upgradeFile($file);
        }
    }

    private function upgradeFile(File $file)
    {
        foreach ($file->getFileVersions() as $fileVersion) {
            $this->upgradeFileVersion($fileVersion);
        }
    }

    private function upgradeFileVersion(FileVersion $fileVersion)
    {
        $fileVersion->setDefaultMeta($fileVersion->getMeta()->first());
    }
}
