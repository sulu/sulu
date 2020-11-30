<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Functional\Controller;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class AdminControllerTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        parent::setUp();
        $this->purgeDatabase();
        $this->initPhpcr();
    }

    public function testRulesConfig()
    {
        static::jsonRequest($this->client, 'GET', '/admin/config');

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $audienceTargetingConfig = $response->sulu_audience_targeting;

        $this->assertGreaterThan(1, (array) $audienceTargetingConfig->targetGroupRules);
        $this->assertEquals('Locale', $audienceTargetingConfig->targetGroupRules->locale->name);
        $this->assertEquals('input', $audienceTargetingConfig->targetGroupRules->locale->type->name);
        $this->assertEquals('locale', $audienceTargetingConfig->targetGroupRules->locale->type->options->name);
    }

    public function testTargetGroupsListMetadataAction()
    {
        static::jsonRequest($this->client, 'GET', '/admin/metadata/list/target_groups');

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertObjectHasAttribute('id', $response);
        $this->assertObjectHasAttribute('title', $response);

        $this->assertEquals('ID', $response->id->label);
        $this->assertEquals('string', $response->id->type);
    }

    public function testTargetGroupsFormMetadataAction()
    {
        static::jsonRequest($this->client, 'GET', '/admin/metadata/form/target_group_details');

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $form = $response->form;

        $this->assertEquals('Title', $form->title->label);

        $schema = $response->schema;
        $this->assertEquals(['title', 'priority'], $schema->required);
    }
}
