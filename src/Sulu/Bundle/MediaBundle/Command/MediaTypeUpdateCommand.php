<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Command;

use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Media\TypeManager\TypeManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MediaTypeUpdateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('sulu:media:type:update')
            ->setDescription('Update all media type by the set configuration');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('SuluMediaBundle:Media');
        $medias = $repo->findAll();
        /** @var TypeManagerInterface $typeManager */
        $typeManager = $this->getContainer()->get('sulu_media.type_manager');
        $counter = 0;
        /** @var MediaInterface $media */
        foreach ($medias as $media) {
            /** @var File $file */
            foreach ($media->getFiles() as $file) {
                /** @var FileVersion $fileVersion */
                foreach ($file->getFileVersions() as $fileVersion) {
                    if ($fileVersion->getVersion() == $file->getVersion()) {
                        $mediaTypeId = $typeManager->getMediaType($fileVersion->getMimeType());
                        if ($media->getType()->getId() != $mediaTypeId) {
                            $oldType = $media->getType();
                            $newType = $typeManager->get($mediaTypeId);
                            $media->setType($newType);
                            $em->persist($media);
                            ++$counter;
                            $output->writeln(sprintf('Media with id <comment>%s</comment> change from type <comment>%s</comment> to <comment>%s</comment>', $media->getId(), $oldType->getName(), $newType->getName()));
                        }
                    }
                }
            }
        }
        if ($counter) {
            $em->flush();
            $output->writeln(sprintf('<info>SUCCESS FULLY UPDATED (%s)</info>', $counter));
        } else {
            $output->writeln('<comment>Nothing to update</comment>');
        }
    }
}
