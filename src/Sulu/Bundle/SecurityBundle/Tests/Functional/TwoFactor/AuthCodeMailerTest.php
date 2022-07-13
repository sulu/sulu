<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Functional\TwoFactor;

use Sulu\Bundle\SecurityBundle\Entity\UserTwoFactor;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\Mailer\DataCollector\MessageDataCollector;

class AuthCodeMailerTest extends SuluTestCase
{
    public function testSendAuthCode(): void
    {
        $client = $this->createClient();
        $user = $this->getTestUser();
        $twoFactor = $user->getTwoFactor() ?: new UserTwoFactor($user);
        $user->setTwoFactor($twoFactor);
        $twoFactor->setMethod('email');
        $user->setEmail('sulu@localhost');
        $user->setEmailAuthCode('1234');
        $this->getEntityManager()->flush();
        $client->enableProfiler();

        $client->request('POST', '/admin/login', [
            '_username' => 'test',
            '_password' => 'test',
        ]);

        $this->assertHttpStatusCode(302, $client->getResponse());
        /** @var Profile $profile */
        $profile = $client->getProfile();
        /** @var MessageDataCollector $mailCollector */
        $mailCollector = $profile->getCollector('mailer');
        $messages = $mailCollector->getEvents()->getMessages();
        $this->assertCount(1, $messages);
        $message = $messages[0];
        $this->assertInstanceOf(TemplatedEmail::class, $message);
        $this->assertSame('Authentication Code', $message->getSubject());
        /** @var string $textBody */
        $textBody = $message->getTextBody();
        $this->assertStringContainsString('Auth Code: ', $textBody);
        /** @var string $htmlBody */
        $htmlBody = $message->getHtmlBody();
        $this->assertStringContainsString('Auth Code: ', $htmlBody);
    }

    public static function tearDownAfterClass(): void
    {
        $user = static::getTestUser();
        /** @var UserTwoFactor $twoFactor */
        $twoFactor = $user->getTwoFactor();
        $twoFactor->setMethod(null);
        static::getEntityManager()->flush();
    }
}
