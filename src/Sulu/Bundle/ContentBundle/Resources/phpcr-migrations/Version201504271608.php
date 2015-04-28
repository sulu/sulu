<?php

namespace Sulu\Bundle\ContentBundle;

use DTL\PhpcrMigrations\VersionInterface;
use PHPCR\SessionInterface;
use Sulu\Component\PHPCR\NodeTypes\Content\HomeNodeType;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version201504271608 implements VersionInterface, ContainerAwareInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function up(SessionInterface $session)
    {
        $workspace = $session->getWorkspace();
        $nodeTypeManager = $workspace->getNodeTypeManager();
        $nodeTypeManager->registerNodeType(
            new HomeNodeType(),
            true
        );

        $this->migrateHome($session, 'sulu:page', 'sulu:home', true);
    }

    public function down(SessionInterface $session)
    {
        $this->migrateHome($session, 'sulu:home', 'sulu:page', false);
    }

    private function migrateHome(SessionInterface $session, $from, $to, $referenceWebspace)
    {
        $webspaceManager = $this->container->get('sulu_core.webspace.webspace_manager');
        $pathRegistry = $this->container->get('sulu_document_manager.path_segment_registry');

        $webspaces = $webspaceManager->getWebspaceCollection();

        foreach ($webspaces as $webspace) {
            $webspacePath = sprintf('/%s/%s', $pathRegistry->getPathSegment('base'), $webspace->getKey());
            $homeNodeName = $pathRegistry->getPathSegment('content');
            $webspace = $session->getNode($webspacePath);

            if ($referenceWebspace) {
                $webspace->addMixin('mix:referenceable');
            } else {
                $webspace->removeMixin('mix:referenceable');
            }

            $homeNode = $webspace->getNode($homeNodeName);
            $homeNode->addMixin($to);
            $homeNode->removeMixin($from);
        }
    }
}
