<?php

namespace Sulu\Bundle\TestBundle\Testing;

use Symfony\Cmf\Component\Testing\Functional\BaseTestCase;

class SuluTestCase extends BaseTestCase
{
    protected function getTestUserId()
    {
        $user = $this->em->getRepository('Sulu\Bundle\TestBundle\Entity\TestUser')
            ->findOneByUsername('test');

        return $user->getId();
    }

    protected function initPhpcr()
    {
        $session = $this->db('PHPCR')->getOm()->getPhpcrSession();

        if ($session->nodeExists('/cmf')) {
            $session->getNode('/cmf')->remove();
        }

        $session->save();

        $cmf = $session->getRootNode()->addNode('cmf');

        // we should use the doctrinephpcrbundle repository initializer to do this.
        $webspace = $cmf->addNode('sulu_io');
        $nodes = $webspace->addNode('routes');
        $nodes->addNode('de');
        $nodes->addNode('en');
        $content = $webspace->addNode('contents');
        $content->setProperty('i18n:en-template', 'default');
        $content->setProperty('i18n:en-creator', 1);
        $content->setProperty('i18n:en-created', new \DateTime());
        $content->setProperty('i18n:en-changer', 1);
        $content->setProperty('i18n:en-changed', new \DateTime());
        $content->addMixin('sulu:content');
        $webspace->addNode('temp');

        $session->save();
    }
}
