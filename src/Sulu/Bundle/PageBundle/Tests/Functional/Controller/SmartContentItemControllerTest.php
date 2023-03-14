<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManager;
use PHPCR\SessionInterface;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\PageBundle\Form\Type\PageDocumentType;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Form\FormFactoryInterface;

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
     * @var TagInterface
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

    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
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

        $user = $this->getContainer()->get('test_user_provider')->getUser();

        $role1 = new Role();
        $role1->setName('Role1');
        $role1->setSystem('Sulu');
        $this->em->persist($role1);

        $userRole1 = new UserRole();
        $userRole1->setRole($role1);
        $userRole1->setUser($user);
        $userRole1->setLocale(\json_encode(['de', 'en']));
        $user->addUserRole($userRole1);
        $this->em->persist($userRole1);

        $permission1 = new Permission();
        $permission1->setPermissions(122);
        $permission1->setRole($role1);
        $permission1->setContext('sulu.webspaces.sulu_io');
        $this->em->persist($permission1);

        $this->tag1 = $this->getContainer()->get('sulu.repository.tag')->createNew();
        $this->tag1->setName('tag1');
        $this->em->persist($this->tag1);

        $this->em->flush();
    }

    private function initPages(): void
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
     * @return PageDocument
     */
    private function savePage($template, $data, $parent, $publish = false, $tags = [])
    {
        $data = \array_merge(
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
        $form = $this->formFactory->create(PageDocumentType::class, $document, $options);

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

    public function testGetItems(): void
    {
        $this->client->jsonRequest(
            'GET',
            '/api/items?webspace=sulu_io&locale=en&dataSource=' . $this->team->getUuid() .
            '&provider=pages&excluded=' . $this->team->getUuid()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(
            [
                'id' => $this->team->getUuid(),
                'title' => 'Team',
                'path' => '/team',
                'image' => null,
            ],
            $result['datasource']
        );
        $this->assertEquals(
            [
                [
                    'id' => $this->johannes->getUuid(),
                    'title' => 'Johannes',
                    'publishedState' => false,
                    'url' => '/team/johannes',
                    'published' => null,
                    'webspace' => 'sulu_io',
                ],
                [
                    'id' => $this->daniel->getUuid(),
                    'title' => 'Daniel',
                    'publishedState' => false,
                    'url' => '/team/daniel',
                    'published' => null,
                    'webspace' => 'sulu_io',
                ],
                [
                    'id' => $this->thomas->getUuid(),
                    'title' => 'Thomas',
                    'publishedState' => false,
                    'url' => '/team/thomas',
                    'published' => null,
                    'webspace' => 'sulu_io',
                ],
            ],
            $result['_embedded']['items']
        );
    }

    public function testGetItemsExcluded(): void
    {
        $this->client->jsonRequest(
            'GET',
            '/api/items?webspace=sulu_io&locale=en&dataSource=' . $this->team->getUuid() .
            '&provider=pages&excluded=' . $this->johannes->getUuid()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(
            [
                'id' => $this->team->getUuid(),
                'title' => 'Team',
                'path' => '/team',
                'image' => null,
            ],
            $result['datasource']
        );
        $this->assertEquals(
            [
                [
                    'id' => $this->daniel->getUuid(),
                    'title' => 'Daniel',
                    'publishedState' => false,
                    'url' => '/team/daniel',
                    'published' => null,
                    'webspace' => 'sulu_io',
                ],
                [
                    'id' => $this->thomas->getUuid(),
                    'title' => 'Thomas',
                    'publishedState' => false,
                    'url' => '/team/thomas',
                    'published' => null,
                    'webspace' => 'sulu_io',
                ],
            ],
            $result['_embedded']['items']
        );
    }

    public function testGetItemsMultipleExcluded(): void
    {
        $this->client->jsonRequest(
            'GET',
            '/api/items?webspace=sulu_io&locale=en&dataSource='
            . $this->team->getUuid()
            . '&provider=pages&excluded='
            . $this->johannes->getUuid()
            . ','
            . $this->daniel->getUuid()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(
            [
                'id' => $this->team->getUuid(),
                'title' => 'Team',
                'path' => '/team',
                'image' => null,
            ],
            $result['datasource']
        );
        $this->assertEquals(
            [
                [
                    'id' => $this->thomas->getUuid(),
                    'title' => 'Thomas',
                    'publishedState' => false,
                    'url' => '/team/thomas',
                    'published' => null,
                    'webspace' => 'sulu_io',
                ],
            ],
            $result['_embedded']['items']
        );
    }

    public function testGetItemsWithParams(): void
    {
        $this->client->jsonRequest(
            'GET',
            '/api/items?webspace=sulu_io&locale=en&dataSource=' . $this->team->getUuid() .
            '&provider=pages&excluded=' . $this->johannes->getUuid() .
            '&params={"max_per_page":{"value":"5","type":"string"},' .
            '"properties":{"value":{"title":{"value":"title","type":"string"}},"type":"collection"}}'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(
            [
                'id' => $this->team->getUuid(),
                'title' => 'Team',
                'path' => '/team',
                'image' => null,
            ],
            $result['datasource']
        );
        $this->assertEquals(
            [
                [
                    'id' => $this->daniel->getUuid(),
                    'title' => 'Daniel',
                    'publishedState' => false,
                    'url' => '/team/daniel',
                    'published' => null,
                    'webspace' => 'sulu_io',
                ],
                [
                    'id' => $this->thomas->getUuid(),
                    'title' => 'Thomas',
                    'publishedState' => false,
                    'url' => '/team/thomas',
                    'published' => null,
                    'webspace' => 'sulu_io',
                ],
            ],
            $result['_embedded']['items']
        );
    }

    public function testGetItemsWithParamsAndNoType(): void
    {
        $this->client->jsonRequest(
            'GET',
            '/api/items?webspace=sulu_io&locale=en&dataSource=' . $this->team->getUuid() .
            '&provider=pages&excluded=' . $this->johannes->getUuid() .
            '&params={"max_per_page":{"value":"5"},' .
            '"properties":{"value":{"title":{"value":"title","type":"string"}},"type":"collection"}}'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(
            [
                'id' => $this->team->getUuid(),
                'title' => 'Team',
                'path' => '/team',
                'image' => null,
            ],
            $result['datasource']
        );
        $this->assertEquals(
            [
                [
                    'id' => $this->daniel->getUuid(),
                    'title' => 'Daniel',
                    'publishedState' => false,
                    'url' => '/team/daniel',
                    'published' => null,
                    'webspace' => 'sulu_io',
                ],
                [
                    'id' => $this->thomas->getUuid(),
                    'title' => 'Thomas',
                    'publishedState' => false,
                    'url' => '/team/thomas',
                    'published' => null,
                    'webspace' => 'sulu_io',
                ],
            ],
            $result['_embedded']['items']
        );
    }

    public function testGetItemsLimit(): void
    {
        $this->client->jsonRequest(
            'GET',
            '/api/items?webspace=sulu_io&locale=en&dataSource=' . $this->team->getUuid() .
            '&provider=pages&excluded=' . $this->team->getUuid() . '&limitResult=2'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(
            [
                'id' => $this->team->getUuid(),
                'title' => 'Team',
                'path' => '/team',
                'image' => null,
            ],
            $result['datasource']
        );
        $this->assertEquals(
            [
                [
                    'id' => $this->johannes->getUuid(),
                    'title' => 'Johannes',
                    'publishedState' => false,
                    'url' => '/team/johannes',
                    'published' => null,
                    'webspace' => 'sulu_io',
                ],
                [
                    'id' => $this->daniel->getUuid(),
                    'title' => 'Daniel',
                    'publishedState' => false,
                    'url' => '/team/daniel',
                    'published' => null,
                    'webspace' => 'sulu_io',
                ],
            ],
            $result['_embedded']['items']
        );
    }

    public function testGetItemsTags(): void
    {
        $this->client->jsonRequest(
            'GET',
            '/api/items?webspace=sulu_io&locale=en&dataSource=' . $this->team->getUuid() .
            '&provider=pages&excluded=' . $this->team->getUuid() . '&limitResult=2&tags=' . $this->tag1->getName()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(
            [
                'id' => $this->team->getUuid(),
                'title' => 'Team',
                'path' => '/team',
                'image' => null,
            ],
            $result['datasource']
        );
        $this->assertEquals(
            [
                [
                    'id' => $this->johannes->getUuid(),
                    'title' => 'Johannes',
                    'publishedState' => false,
                    'url' => '/team/johannes',
                    'published' => null,
                    'webspace' => 'sulu_io',
                ],
            ],
            $result['_embedded']['items']
        );
    }
}
