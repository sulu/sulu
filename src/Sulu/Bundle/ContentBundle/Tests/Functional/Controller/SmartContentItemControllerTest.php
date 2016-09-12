<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Controller;

use Doctrine\ORM\EntityManager;
use PHPCR\SessionInterface;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * @group nodecontroller
 */
class SmartContentItemControllerTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var PageDocument
     */
    private $team;

    /**
     * @var PageDocument
     */
    private $johannes;

    /**
     * @var PageDocument
     */
    private $daniel;

    /**
     * @var PageDocument
     */
    private $thomas;

    /**
     * @var Tag
     */
    private $tag1;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var DocumentInspector
     */
    private $inspector;

    protected function setUp()
    {
        $this->em = $this->getEntityManager();
        $this->session = $this->getContainer()->get('doctrine_phpcr')->getConnection();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->formFactory = $this->getContainer()->get('form.factory');
        $this->structureManager = $this->getContainer()->get('sulu.content.structure_manager');
        $this->inspector = $this->getContainer()->get('sulu_document_manager.document_inspector');

        $this->initOrm();
        $this->initPhpcr();
        $this->initPages();
    }

    protected function initOrm()
    {
        $this->purgeDatabase();

        $contact = new Contact();
        $contact->setFirstName('Max');
        $contact->setLastName('Mustermann');
        $this->em->persist($contact);
        $this->em->flush();

        $emailType = new EmailType();
        $emailType->setName('Private');
        $this->em->persist($emailType);
        $this->em->flush();

        $email = new Email();
        $email->setEmail('max.mustermann@muster.at');
        $email->setEmailType($emailType);
        $this->em->persist($email);
        $this->em->flush();

        $role1 = new Role();
        $role1->setName('Role1');
        $role1->setSystem('Sulu');
        $this->em->persist($role1);
        $this->em->flush();

        $user = new User();
        $user->setUsername('admin');
        $user->setPassword('securepassword');
        $user->setSalt('salt');
        $user->setLocale('de');
        $user->setContact($contact);
        $this->em->persist($user);
        $this->em->flush();

        $userRole1 = new UserRole();
        $userRole1->setRole($role1);
        $userRole1->setUser($user);
        $userRole1->setLocale(json_encode(['de', 'en']));
        $this->em->persist($userRole1);
        $this->em->flush();

        $permission1 = new Permission();
        $permission1->setPermissions(122);
        $permission1->setRole($role1);
        $permission1->setContext('Context 1');
        $this->em->persist($permission1);
        $this->em->flush();

        $this->tag1 = new Tag();
        $this->tag1->setName('tag1');
        $this->em->persist($this->tag1);
        $this->em->flush();
    }

    private function initPages()
    {
        $this->team = $this->savePage(
            'simple',
            [
                'title' => 'Team',
                'url' => '/team',
            ],
            $this->session->getNode('/cmf/sulu_io/contents')->getIdentifier(),
            true
        );
        $this->johannes = $this->savePage(
            'simple',
            [
                'title' => 'Johannes',
                'url' => '/team/johannes',
            ],
            $this->team->getUuid(),
            false,
            [$this->tag1->getId()]
        );
        $this->daniel = $this->savePage(
            'simple',
            [
                'title' => 'Daniel',
                'url' => '/team/daniel',
            ],
            $this->team->getUuid()
        );
        $this->thomas = $this->savePage(
            'simple',
            [
                'title' => 'Thomas',
                'url' => '/team/thomas',
            ],
            $this->team->getUuid()
        );
    }

    /**
     * @param $template
     * @param $data
     * @param $parent
     *
     * @return PageDocument
     */
    private function savePage($template, $data, $parent, $publish = false, $tags = [])
    {
        $data = array_merge(
            [
                'template' => $template,
                'parent' => $parent,
                'workflowStage' => 2,
                'webspace_key' => 'sulu_io',
            ],
            $data
        );

        $document = $this->documentManager->create('page');

        $options = ['csrf_protection' => false, 'webspace_key' => 'sulu_io'];
        $form = $this->formFactory->create('page', $document, $options);

        $clearMissing = false;
        $form->submit($data, $clearMissing);

        $this->documentManager->persist(
            $document,
            'en',
            [
                'user' => 1,
            ]
        );
        if ($publish) {
            $this->documentManager->publish($document, 'en');
        }
        $this->documentManager->flush();

        $node = $this->inspector->getNode($document);
        $node->setProperty('i18n:en-excerpt-tags', $tags);
        $node->getSession()->save();

        return $document;
    }

    public function testGetItems()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/items?webspace=sulu_io&locale=en&dataSource=' . $this->team->getUuid() .
            '&provider=content&excluded=' . $this->team->getUuid()
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(
            ['id' => $this->team->getUuid(), 'title' => 'Team', 'path' => '/team'],
            $result['datasource']
        );
        $this->assertEquals(
            [
                ['id' => $this->johannes->getUuid(), 'title' => 'Johannes', 'publishedState' => false, 'url' => '/team/johannes'],
                ['id' => $this->daniel->getUuid(), 'title' => 'Daniel', 'publishedState' => false, 'url' => '/team/daniel'],
                ['id' => $this->thomas->getUuid(), 'title' => 'Thomas', 'publishedState' => false, 'url' => '/team/thomas'],
            ],
            $result['_embedded']['items']
        );
    }

    public function testGetItemsExcluded()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/items?webspace=sulu_io&locale=en&dataSource=' . $this->team->getUuid() .
            '&provider=content&excluded=' . $this->johannes->getUuid()
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(
            ['id' => $this->team->getUuid(), 'title' => 'Team', 'path' => '/team'],
            $result['datasource']
        );
        $this->assertEquals(
            [
                ['id' => $this->daniel->getUuid(), 'title' => 'Daniel', 'publishedState' => false, 'url' => '/team/daniel'],
                ['id' => $this->thomas->getUuid(), 'title' => 'Thomas', 'publishedState' => false, 'url' => '/team/thomas'],
            ],
            $result['_embedded']['items']
        );
    }

    public function testGetItemsWithParams()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/items?webspace=sulu_io&locale=en&dataSource=' . $this->team->getUuid() .
            '&provider=content&excluded=' . $this->johannes->getUuid() .
            '&params={"max_per_page":{"value":"5","type":"string"},' .
            '"properties":{"value":{"title":{"value":"title","type":"string"}},"type":"collection"}}'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(
            ['id' => $this->team->getUuid(), 'title' => 'Team', 'path' => '/team'],
            $result['datasource']
        );
        $this->assertEquals(
            [
                ['id' => $this->daniel->getUuid(), 'title' => 'Daniel', 'publishedState' => false, 'url' => '/team/daniel'],
                ['id' => $this->thomas->getUuid(), 'title' => 'Thomas', 'publishedState' => false, 'url' => '/team/thomas'],
            ],
            $result['_embedded']['items']
        );
    }

    public function testGetItemsLimit()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/items?webspace=sulu_io&locale=en&dataSource=' . $this->team->getUuid() .
            '&provider=content&excluded=' . $this->team->getUuid() . '&limitResult=2'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(
            ['id' => $this->team->getUuid(), 'title' => 'Team', 'path' => '/team'],
            $result['datasource']
        );
        $this->assertEquals(
            [
                ['id' => $this->johannes->getUuid(), 'title' => 'Johannes', 'publishedState' => false, 'url' => '/team/johannes'],
                ['id' => $this->daniel->getUuid(), 'title' => 'Daniel', 'publishedState' => false, 'url' => '/team/daniel'],
            ],
            $result['_embedded']['items']
        );
    }

    public function testGetItemsTags()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/items?webspace=sulu_io&locale=en&dataSource=' . $this->team->getUuid() .
            '&provider=content&excluded=' . $this->team->getUuid() . '&limitResult=2&tags[]=' . $this->tag1->getName()
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(
            ['id' => $this->team->getUuid(), 'title' => 'Team', 'path' => '/team'],
            $result['datasource']
        );
        $this->assertEquals(
            [
                ['id' => $this->johannes->getUuid(), 'title' => 'Johannes', 'publishedState' => false, 'url' => '/team/johannes'],
            ],
            $result['_embedded']['items']
        );
    }
}
