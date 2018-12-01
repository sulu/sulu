<?php

namespace App\Functional;

use Symfony\Component\Panther\PantherTestCase;
use Sulu\Component\HttpKernel\SuluKernel;

class E2ETest extends PantherTestCase
{
    public function testMyApp()
    {
        $client = static::createPantherClient();
        $crawler = $client->request('GET', '/admin');

        $this->assertContains('SULU', $crawler->filter('title')->html());

        $client->waitFor('form');

        $crawler->selectButton('button:last-child')->form([
            '_username' => 'admin',
            '_password' => 'admin'
        ]);
    }

    protected static function createKernel(array $options = [])
    {
        if (null === static::$class) {
            static::$class = static::getKernelClass();
        }

        if (isset($options['environment'])) {
            $env = $options['environment'];
        } elseif (isset($_ENV['APP_ENV'])) {
            $env = $_ENV['APP_ENV'];
        } elseif (isset($_SERVER['APP_ENV'])) {
            $env = $_SERVER['APP_ENV'];
        } else {
            $env = 'test';
        }

        if (isset($options['debug'])) {
            $debug = $options['debug'];
        } elseif (isset($_ENV['APP_DEBUG'])) {
            $debug = $_ENV['APP_DEBUG'];
        } elseif (isset($_SERVER['APP_DEBUG'])) {
            $debug = $_SERVER['APP_DEBUG'];
        } else {
            $debug = true;
        }

        if (isset($options['sulu.context'])) {
            $suluContext = $options['sulu.context'];
        } else {
            $suluContext = SuluKernel::CONTEXT_ADMIN;
        }

        return new static::$class($env, $debug, $suluContext);
    }
}
