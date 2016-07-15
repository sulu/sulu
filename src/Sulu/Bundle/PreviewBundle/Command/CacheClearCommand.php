<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Command;

use Sulu\Bundle\PreviewBundle\Preview\Renderer\KernelFactoryInterface;
use Sulu\Component\HttpKernel\SuluKernel;
use Symfony\Bundle\FrameworkBundle\Command\CacheClearCommand as BaseCacheClearCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

class CacheClearCommand extends BaseCacheClearCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $nullOutput = new NullOutput();
        $io = new SymfonyStyle($input, $output);

        /** @var KernelInterface $kernel */
        $kernel = $this->getContainer()->get('kernel');
        $context = $this->getContainer()->getParameter('sulu.context');

        $io->comment(sprintf('Clearing the <info>%s cache</info> for the <info>%s</info> environment with debug <info>%s</info>',
            $context, $kernel->getEnvironment(), var_export($kernel->isDebug(), true)));

        parent::execute($input, $nullOutput);

        $io->success(sprintf('%s cache for the "%s" environment (debug=%s) was successfully cleared.',
            ucfirst($context), $kernel->getEnvironment(), var_export($kernel->isDebug(), true)));

        if (SuluKernel::CONTEXT_ADMIN === $context) {
            /** @var KernelFactoryInterface $kernelFactory */
            $kernelFactory = $this->getContainer()->get('sulu_preview.preview.kernel_factory');
            $previewKernel = $kernelFactory->create($kernel->getEnvironment());

            // set preview container
            $this->setContainer($previewKernel->getContainer());

            $io->comment(sprintf('Clearing the <info>preview cache</info> for the <info>%s</info> environment with debug <info>%s</info>',
                $kernel->getEnvironment(), var_export($kernel->isDebug(), true)));

            parent::execute($input, $nullOutput);

            $io->success(sprintf('Preview cache for the "%s" environment (debug=%s) was successfully cleared.',
                $kernel->getEnvironment(), var_export($kernel->isDebug(), true)));
        }
    }
}
