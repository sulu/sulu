<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle;

use PHPCR\Migrations\VersionInterface;
use PHPCR\SessionInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Version201903271333 implements VersionInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function up(SessionInterface $session)
    {
        $liveSession = $this->container->get('sulu_document_manager.live_session');
        $this->upgrade($liveSession);
        $this->upgrade($session);

        $liveSession->save();
        $session->save();
    }

    public function down(SessionInterface $session)
    {
        $liveSession = $this->container->get('sulu_document_manager.live_session');

        $this->downgrade($liveSession);
        $this->downgrade($session);

        $liveSession->save();
        $session->save();
    }

    private function upgrade(SessionInterface $session)
    {
        $queryManager = $session->getWorkspace()->getQueryManager();

        $query = 'SELECT * FROM [nt:unstructured] WHERE ([jcr:mixinTypes] = "sulu:page" OR [jcr:mixinTypes] = "sulu:home")';
        $rows = $queryManager->createQuery($query, 'JCR-SQL2')->execute();

        foreach ($rows as $row) {
            $node = $row->getNode();

            foreach ($node->getProperties() as $property) {
                $propertyValue = $property->getValue();
                if (is_string($propertyValue) && false !== strpos($propertyValue, '<sulu:media')) {
                    $newPropertyValue = preg_replace_callback(
                        '/<sulu:media (.*?)>(.*?)<\/sulu:media>/',
                        function($match) {
                            return '<sulu:link provider="media" target="_self" ' . str_replace('id=', 'href=', $match[1]) . '>' . $match[2] . '</sulu:link>';
                        },
                        $propertyValue
                    );

                    $property->setValue($newPropertyValue);
                }
            }
        }
    }

    private function downgrade(SessionInterface $session)
    {
        $queryManager = $session->getWorkspace()->getQueryManager();

        $query = 'SELECT * FROM [nt:unstructured] WHERE ([jcr:mixinTypes] = "sulu:page" OR [jcr:mixinTypes] = "sulu:home")';
        $rows = $queryManager->createQuery($query, 'JCR-SQL2')->execute();

        foreach ($rows as $row) {
            $node = $row->getNode();

            foreach ($node->getProperties() as $property) {
                $propertyValue = $property->getValue();
                if (is_string($propertyValue) && false !== strpos($propertyValue, '<sulu:link provider="media"')) {
                    $newPropertyValue = preg_replace_callback(
                        '/<sulu:link provider="media" target="_self" href="(.*?)">(.*?)<\/sulu:link>/',
                        function($match) {
                            return '<sulu:media id="' . $match[1] . '">' . $match[2] . '</sulu:media>';
                        },
                        $propertyValue
                    );

                    $property->setValue($newPropertyValue);
                }
            }
        }
    }
}
