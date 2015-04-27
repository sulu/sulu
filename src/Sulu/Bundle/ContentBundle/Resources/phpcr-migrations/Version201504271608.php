<?php

namespace Sulu\Bundle\ContentBundle;

use DTL\PhpcrMigrations\VersionInterface;
use PHPCR\SessionInterface;
use Sulu\Component\PHPCR\NodeTypes\Content\HomeNodeType;

class Version201504271608 implements VersionInterface
{
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
        $workspace = $session->getWorkspace();
        $sql2 = <<<EOT
SELECT * FROM [nt:unstructured] AS webspace INNER JOIN [nt:unstructured] AS home ON ISCHILDNODE(home, webspace) WHERE home.[jcr:mixinTypes] = '%s'
EOT
        ;

        $queryManager = $workspace->getQueryManager();
        $query = $queryManager->createQuery(sprintf($sql2, $from), 'JCR-SQL2');
        $results = $query->execute();

        foreach ($results->getRows() as $result) {
            $webspace = $result->getNode('webspace');

            if ($referenceWebspace) {
                $webspace->addMixin('mix:referenceable');
            } else {
                $webspace->removeMixin('mix:referenceable');
            }

            $node = $result->getNode('home');
            $node->removeMixin($from);
            $node->addMixin($to);
        }
    }
}
