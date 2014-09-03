<?php

namespace EventListener;

class Reindexer
{
    protected $contentMapper;

    public function __construct(WebspaceManager $webspaceManager, SessionInterface $session, ContentMapper $contentMapper)
    {
        $this->contentMapper = $contentMapper;
    }

    public function reindex()
    {
        $webspaces = $this->webspace->getWebspaceCollection();

        foreach ($webspaceCollection as $webspace) {
            $sql2 = 'SELECT * FROM [nt:unstructured] WHERE [jcr:mixinTypes] = "sulu:content"';

            $queryManager = $this->session->getWorkspace()->getQueryManager();

            $query = $queryManager->createQuery($sql2, 'JCR-SQL2');

            $res = $query->execute();

            foreach ($res->getRows() as $row) {
                foreach ($locales as $locale) {
                    $webspace = preg_match('{/cmf/(.*?).*$}', $row->getNode()->getPath(), $matches);
                    $structure = $this->contentMapAper->loadByNode($row->getNode());

                    $this->searchManager->index($structure, $locale, $webspace);
                }
            }
        }
    }
}
