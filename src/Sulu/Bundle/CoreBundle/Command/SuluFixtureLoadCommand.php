<?php

namespace Sulu\Bundle\CoreBundle\Command;

use Sulu\Component\Content\Mapper\ContentMapperRequest;
use Sulu\Component\Content\StructureInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Load fixture command.
 */
class SuluFixtureLoadCommand extends ContainerAwareCommand
{
    public function configure()
    {
        $this->setName('sulu:fixtures:load');
        $this->setDescription('Load fixtures');
        $this->setHelp(<<<EOT
The %command.name% command loads fixtures in YAML format as follows:

        webspaces:
            sulu_io:
                pages:
                    -
                        locale: en
                        template: default
                        data:
                            url: /article-one
                            title: Article One
                            description: My first article
                        children:
                            -
                                locale: de
                                template: default
                                data:
                                    url: /article-one/article-two
                                    title: Article Two
                                    description: My second article
        snippets:
            -
                locale: de
                template: hotel
                data:
                    name: The Grand Budapest
                    description: Located in the fictional Republic of Zubrowka

By default all YAML files in the <info>app/Resources/fixtures</info> directory will
be parsed. This directory can be overridden with the <info>path</info> argument.
EOT
        );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->userId = 1;
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');
        $this->webspaceManager = $this->getContainer()->get('sulu_core.webspace.webspace_manager');
        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $this->output = $output;

        $this->doExecute();
    }

    protected function doExecute()
    {
        $fixturePath = sprintf('%s/%s',
            $this->getContainer()->getParameter('kernel.root_dir'),
            'Resources/fixtures'
        );

        if (!file_exists($fixturePath)) {
            $this->output->writeln(sprintf(
                'Sulu fixtures path "%s" does not exist, skipping.',
                $fixturePath
            ));

            return 0;
        }

        $dirHandle = opendir($fixturePath);
        $count = 0;

        // purge snippets
        $snippetNode = $this->sessionManager->getSnippetNode();
        foreach ($snippetNode->getNodes() as $node) {
            foreach ($node->getNodes() as $node) {
                $node->remove();
            }
        }

        while ($file = readdir($dirHandle)) {
            if (substr($file, -4) !== '.yml') {
                continue;
            }
            $count++;

            $this->output->writeln('Loading: ' . $file);

            $data = array_merge(
                array(
                    'webspaces' => array(),
                    'snippets' => array(),
                ),
                Yaml::parse(file_get_contents($fixturePath . '/' . $file))
            );

            foreach ($data['webspaces'] as $webspaceKey => $webspaceData) {
                $this->processWebspace($webspaceKey, $webspaceData);
            }

            foreach ($data['snippets'] as $snippet) {
                $this->processSnippet($snippet);
            }
        }

        $this->output->writeln(sprintf('Loaded "%s" fixture files.', $count));
    }

    private function processWebspace($webspaceKey, $webspaceData)
    {
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);

        // purge
        $this->output->writeln('<info>Purging webspace: </info>' . $webspaceKey);
        foreach ($webspace->getLocalizations()  as $localization) {
            $routeNode = $this->sessionManager->getRouteNode($webspaceKey, $localization->getLocalization());

            foreach ($routeNode->getNodes() as $node) {
                $node->remove();
            }
        }

        $contentNode = $this->sessionManager->getContentNode($webspaceKey);
        foreach ($contentNode->getNodes() as $node) {
            $node->remove();
        }

        $this->sessionManager->getSession()->save();

        foreach ($webspaceData['pages'] as $i => $page) {
            $this->processPage($webspaceKey, $page);
        }
    }

    private function processPage($webspaceKey, $page, $parent = null)
    {
        static $i = 0;
        $this->output->writeln('<comment> - Page: </comment>' . ++$i);
        $page = array_merge(array(
            'template' => 'default',
            'published' => true,
            'shadow' => null,
            'locale' => 'de',
            'data' => array(),
            'children' => array(),
        ), $page);

        $request = ContentMapperRequest::create()
            ->setType('page')
            ->setTemplateKey($page['template'])
            ->setWebspaceKey($webspaceKey)
            ->setUserId($this->userId)
            ->setState($page['published'] ? StructureInterface::STATE_PUBLISHED : StructureInterface::STATE_TEST)
            ->setIsShadow($page['shadow'] ? true : false)
            ->setShadowBaseLanguage($page['shadow'])
            ->setLocale($page['locale'])
            ->setData($page['data']);

        if ($parent) {
            $request->setParentUuid($parent->getUuid());
        }

        $node = $this->contentMapper->saveRequest($request);

        foreach ($page['children'] as $child) {
            $this->processPage($webspaceKey, $child, $node);
        }
    }

    private function processSnippet($snippet)
    {
        static $i = 0;
        $this->output->writeln('<comment> - Snippet: </comment>' . ++$i);

        $snippet = array_merge(array(
            'template' => 'default',
            'published' => true,
            'shadow' => null,
            'locale' => 'de',
            'data' => array(),
            'children' => array(),
        ), $snippet);

        $request = ContentMapperRequest::create()
            ->setType('snippet')
            ->setTemplateKey($snippet['template'])
            ->setUserId($this->userId)
            ->setState($snippet['published'] ? StructureInterface::STATE_PUBLISHED : StructureInterface::STATE_TEST)
            ->setLocale($snippet['locale'])
            ->setData($snippet['data']);

        $this->contentMapper->saveRequest($request);
    }
}
