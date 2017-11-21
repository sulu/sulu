<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Command;

use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Webspace\StructureProvider\WebspaceStructureProvider;
use Sulu\Component\Webspace\Webspace;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Validates pages.
 */
class ValidateWebspacesCommand extends ContainerAwareCommand
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var StructureMetadataFactoryInterface
     */
    private $structureMetadataFactory;

    /**
     * @var ControllerNameParser
     */
    private $controllerNameConverter;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var WebspaceStructureProvider
     */
    private $structureProvider;

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var string
     */
    private $activeTheme;

    protected function configure()
    {
        $this->setName('sulu:content:validate:webspaces')
            ->setDescription('Dumps webspaces and will show an error when template could not be loaded');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->twig = $this->getContainer()->get('twig');
        $this->structureMetadataFactory = $this->getContainer()->get('sulu_content.structure.factory');
        $this->controllerNameConverter = $this->getContainer()->get('controller_name_converter');
        $this->structureManager = $this->getContainer()->get('sulu.content.structure_manager');
        $this->structureProvider = $this->getContainer()->get('sulu.content.webspace_structure_provider');

        if ($this->getContainer()->has('liip_theme.active_theme')) {
            $this->activeTheme = $this->getContainer()->get('liip_theme.active_theme');
        }

        $webspaceManager = $this->getContainer()->get('sulu_core.webspace.webspace_manager');

        /** @var Webspace[] $webspaces */
        $webspaces = $webspaceManager->getWebspaceCollection();

        $messages = '';

        foreach ($webspaces as $webspace) {
            if (null !== $this->activeTheme) {
                $this->activeTheme->setName($webspace->getTheme());
            }

            $this->outputWebspace($webspace);
        }

        $output->writeln($messages);

        if (count($this->errors)) {
            $this->output->writeln(sprintf('<error>%s Errors found.</error>', count($this->errors)));

            return 1;
        }
    }

    /**
     * Output webspace.
     *
     * @param Webspace $webspace
     */
    private function outputWebspace(Webspace $webspace)
    {
        $this->output->writeln(
            sprintf(
                '<info>%s</info> - <info>%s</info>',
                $webspace->getKey(),
                $webspace->getName()
            )
        );

        $this->outputWebspaceDefaultTemplates($webspace);
        $this->outputWebspacePageTemplates($webspace);
        $this->outputWebspaceTemplates($webspace);
        $this->outputWebspaceLocalizations($webspace);
    }

    /**
     * Output webspace default templates.
     *
     * @param Webspace $webspace
     */
    private function outputWebspaceDefaultTemplates(Webspace $webspace)
    {
        $this->output->writeln('Default Templates:');

        foreach ($webspace->getDefaultTemplates() as $type => $template) {
            $this->validatePageTemplate($type, $template);
        }
    }

    /**
     * Output webspace page templates.
     *
     * @param Webspace $webspace
     */
    private function outputWebspacePageTemplates(Webspace $webspace)
    {
        $this->output->writeln('Page Templates:');

        $structures = $this->structureManager->getStructures();

        $checkedTemplates = [];

        foreach ($webspace->getDefaultTemplates() as $template) {
            $checkedTemplates[] = $template;
        }

        foreach ($structures as $structure) {
            $template = $structure->getKey();

            if (!$structure->getInternal() && !in_array($template, $checkedTemplates)) {
                $this->validatePageTemplate('page', $structure->getKey());
            }
        }
    }

    /**
     * Output webspace default templates.
     *
     * @param Webspace $webspace
     */
    private function outputWebspaceTemplates(Webspace $webspace)
    {
        $this->output->writeln('Templates:');

        foreach ($webspace->getTemplates() as $type => $template) {
            $this->validateTemplate($type, $template);
        }
    }

    /**
     * Output webspace localizations.
     *
     * @param Webspace $webspace
     */
    private function outputWebspaceLocalizations(Webspace $webspace)
    {
        $this->output->writeln('Localizations:');

        foreach ($webspace->getAllLocalizations() as $localization) {
            $this->output->writeln(
                sprintf(
                    '    %s',
                    $localization->getLocale()
                )
            );
        }
    }

    /**
     * Validate page templates.
     *
     * @param string $type
     * @param string $template
     */
    private function validatePageTemplate($type, $template)
    {
        $status = '<info>ok</info>';

        try {
            $this->validateStructure($type, $template);
        } catch (\Exception $e) {
            $status = sprintf('<error>failed: %s</error>', $e->getMessage());
            $this->errors[] = $e->getMessage();
        }

        $this->output->writeln(
            sprintf(
                '    %s: %s -> %s',
                $type,
                $template,
                $status
            )
        );
    }

    /**
     * Is template valid.
     *
     * @param string $type
     * @param string $template
     *
     * @return bool
     *
     * @throws \Exception
     */
    private function validateStructure($type, $template)
    {
        $valid = true;

        $metadata = $this->structureMetadataFactory->getStructureMetadata($type, $template);

        if (!$metadata) {
            throw new \RuntimeException(
                sprintf(
                    'Structure meta data not found for "%s".',
                    $type,
                    $template
                )
            );
        }

        foreach (['title', 'url'] as $property) {
            if (!$metadata->hasProperty($property)) {
                throw new \RuntimeException(
                    sprintf(
                        'No property "%s" found in "%s" template.',
                        $property,
                        $metadata->getName()
                    )
                );
            }
        }

        $this->validateTwigTemplate($metadata->view . '.html.twig');
        $this->validateControllerAction($metadata->controller);

        return $valid;
    }

    /**
     * Validate template.
     *
     * @param string $type
     * @param string $template
     */
    private function validateTemplate($type, $template)
    {
        $status = '<info>ok</info>';

        try {
            $this->validateTwigTemplate($template);
        } catch (\Exception $e) {
            $status = sprintf('<error>failed: %s</error>', $e->getMessage());
            $this->errors[] = $e->getMessage();
        }

        $this->output->writeln(
            sprintf(
                '    %s: %s -> %s',
                $type,
                $template,
                $status
            )
        );
    }

    /**
     * Validate twig template.
     *
     * @param string $template
     *
     * @throws \Exception
     */
    private function validateTwigTemplate($template)
    {
        $loader = $this->twig->getLoader();
        if (!$loader->exists($template)) {
            throw new \Exception(sprintf(
                'Unable to find template "%s".',
                $template
            ));
        }
    }

    /**
     * Validate controller action.
     *
     * @param string $controllerAction
     *
     * @throws \Exception
     */
    private function validateControllerAction($controllerAction)
    {
        $result = $this->controllerNameConverter->parse($controllerAction);

        list($class, $method) = explode('::', $result);

        if (!method_exists($class, $method)) {
            $reflector = new \ReflectionClass($class);

            throw new \Exception(sprintf(
                'Controller Action "%s" not exist in "%s" (looked into: %s).',
                $method,
                $class,
                $reflector->getFileName()
            ));
        }
    }
}
