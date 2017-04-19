<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests;

use FOS\HttpCache\SymfonyCache\UserContextSubscriber;
use Ramsey\Uuid\Uuid;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupConditionInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleRepositoryInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupWebspaceInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupWebspaceRepositoryInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\HttpFoundation\Cookie;

require_once __DIR__ . '/../app/AppCache.php';

class CachingTest extends SuluTestCase
{
    /**
     * @var \AppCache
     */
    private $cacheKernel;

    /**
     * @var CookieJar
     */
    private $cookieJar;

    /**
     * @var Client
     */
    private $client;

    public function setUp()
    {
        $this->purgeDatabase();

        $this->cacheKernel = new \AppCache($this->getKernel(['sulu_context' => 'website']), true);
        $this->cookieJar = new CookieJar();
        $this->client = new Client($this->cacheKernel, [], null, $this->cookieJar);
    }

    public function testAudienceTargeting()
    {
        $this->client->request('PURGE', '/');

        $this->cookieJar->clear();

        /** @var TargetGroupRepositoryInterface $targetGroupRepository */
        $targetGroupRepository = $this->getContainer()->get('sulu.repository.target_group');
        /** @var TargetGroupWebspaceRepositoryInterface $targetGroupWebspaceRepository */
        $targetGroupWebspaceRepository = $this->getContainer()->get('sulu.repository.target_group_webspace');
        /** @var TargetGroupRuleRepositoryInterface $targetGroupRuleRepository */
        $targetGroupRuleRepository = $this->getContainer()->get('sulu.repository.target_group_rule');
        /** @var TargetGroupConditionInterface $targetGroupConditionRepository */
        $targetGroupConditionRepository = $this->getContainer()->get('sulu.repository.target_group_condition');

        /** @var TargetGroupInterface $targetGroup */
        $targetGroup = $targetGroupRepository->createNew();
        $targetGroup->setTitle('Test');
        $targetGroup->setPriority(5);
        $targetGroup->setActive(true);
        /** @var TargetGroupWebspaceInterface $targetGroupWebspace */
        $targetGroupWebspace = $targetGroupWebspaceRepository->createNew();
        $targetGroupWebspace->setWebspaceKey('sulu_io');
        $targetGroup->addWebspace($targetGroupWebspace);
        /** @var TargetGroupRuleInterface $targetGroupRule */
        $targetGroupRule = $targetGroupRuleRepository->createNew();
        $targetGroupRule->setTitle('Test');
        $targetGroupRule->setFrequency(TargetGroupRuleInterface::FREQUENCY_HIT);
        /** @var TargetGroupConditionInterface $targetGroupCondition */
        $targetGroupCondition = $targetGroupConditionRepository->createNew();
        $targetGroupCondition->setType('locale');
        $targetGroupCondition->setCondition(['locale' => 'de']);
        $targetGroupRule->addCondition($targetGroupCondition);
        $targetGroup->addRule($targetGroupRule);
        $targetGroupRepository->save($targetGroup);
        $this->getEntityManager()->flush();

        // first request should be cache miss
        $this->resetUserHash();
        $this->client->request('GET', '/');
        $response = $this->client->getResponse();
        $this->assertContains('X-User-Context-Hash', $response->getVary());
        $this->assertContains('miss', $response->headers->get('x-symfony-cache'));
        $this->assertCount(1, $response->headers->getCookies());
        /** @var Cookie $cookie */
        $cookie = $response->headers->getCookies()[0];
        $this->assertEquals('user-context', $cookie->getName());
        $this->assertTrue(Uuid::isValid($cookie->getValue()));

        $cookieNames = array_map(function($cookie) {
            return $cookie->getName();
        }, $response->headers->getCookies());
        $this->assertContains('user-context', $cookieNames);

        // second request should be cache hit
        $this->resetUserHash();
        $this->client->request('GET', '/');
        $response = $this->client->getResponse();
        $this->assertContains('fresh', $response->headers->get('x-symfony-cache'));
        $this->assertCount(0, $response->headers->getCookies());

        // third request from a different client with a different language should be a cache miss,
        // since a new target group should be selected
        $this->resetUserHash();
        $this->cookieJar->clear(); // new client does not have any cookies yet
        $this->client->request('GET', '/', [], [], ['HTTP_ACCEPT_LANGUAGE' => 'de']);
        $response = $this->client->getResponse();
        $this->assertContains('miss', $response->headers->get('x-symfony-cache'));
        $this->assertCount(1, $response->headers->getCookies());
        /** @var Cookie $cookie */
        $cookie = $response->headers->getCookies()[0];
        $this->assertEquals('user-context', $cookie->getName());
        $this->assertTrue(Uuid::isValid($cookie->getValue()));
    }

    private function resetUserHash()
    {
        $userContextSubscriber = $this->client->getKernel()->getEventDispatcher()->getListeners('fos_http_cache.pre_handle')[0][0];
        $userContextSubscriberReflection = new \ReflectionProperty(UserContextSubscriber::class, 'userHash');
        $userContextSubscriberReflection->setAccessible(true);
        $userContextSubscriberReflection->setValue($userContextSubscriber, null);
    }
}
