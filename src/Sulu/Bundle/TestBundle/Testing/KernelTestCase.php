<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TestBundle\Testing;

use InvalidArgumentException;
use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Base test tests which require the Sulu kernel / container.
 */
abstract class KernelTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var KernelInterface[]
     */
    private $kernelStack;

    /**
     * Return the test container.
     *
     * This container will use the default configuration of the kernel.
     *
     * If you require the container for a different kernel environment
     * you should create a new Kernel with the `getKernel` method and
     * retrieve the Container from that.
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        if ($this->container) {
            return $this->container;
        }

        $this->container = $this->getKernel($this->getKernelConfiguration())->getContainer();

        return $this->container;
    }

    /**
     * Create and return new test kernel and pass the sulu.context to it.
     *
     * All kernels created will be added to the kernel stack, at the end of
     * each test the stack will be cleared and all of the kernels will be
     * shutdown.
     *
     * The kernel which has been "found" MUST implement the SuluTestKernel.
     *
     * @throws InvalidArgumentException
     */
    protected function getKernel(array $options = [])
    {
        $this->requireKernel();

        $options = array_merge([
            'environment' => 'test',
            'debug' => true,
            'sulu_context' => 'admin',
        ], $this->getKernelConfiguration(), $options);

        $kernel = new \AppKernel(
            $options['environment'],
            $options['debug'],
            $options['sulu_context']
        );

        if (!$kernel instanceof SuluTestKernel) {
            throw new \InvalidArgumentException(sprintf(
                'All Sulu testing Kernel classes must extend SuluTestKernel, "%s" does not',
                get_class($kernel)
            ));
        }

        $kernel->boot();
        $this->kernelStack[] = $kernel;

        return $kernel;
    }

    /**
     * Return the kernel confguration for the base test case.
     *
     * Typically you will not need to override this (a notable exception is if
     * you need to use the "website" context).
     *
     * @return array
     */
    protected function getKernelConfiguration()
    {
        return [];
    }

    /**
     * Create a new test "client" with a new Kernel instance. The test client
     * is used to make web requests against the kernel instance.
     *
     * Note that the container available via the client and the
     * container available via. `$this>getContainer()` ARE NOT THE SAME.
     *
     * So, if you want to inspect the state of the application that the
     * client is making a request against, you must use the container obtained
     * from the client via. $client->getContainer().
     *
     * @param array $options Kernel options (sulu context, environment, etc)
     * @param array $server Server parameters (e.g. PHP_AUTH_USER, etc)
     *
     * @return Client
     */
    protected function createClient(array $options = [], array $server = [])
    {
        $kernel = $this->getKernel($options);

        $client = $kernel->getContainer()->get('test.client');
        $client->setServerParameters($server);

        return $client;
    }

    /**
     * Require the sulu kernel class file.
     *
     * It is expected that PHPUnit is being executed either:
     *
     * - In the root directory of the Bundle under test.
     * - In the root directory of the sulu-io/sulu library.
     *
     * In each case the Kernel is expected to be in a specific location.
     *
     * TODO: it would be better to directly instantiate the kernel using
     *       autoloading, but this would require some architectural changes as this
     *       is not in-line with how the tests are currently structured.
     */
    private function requireKernel()
    {
        if ($kernelPath = getenv('KERNEL_DIR')) {
            $kernelPaths = [$kernelPath . '/AppKernel.php'];
        } else {
            $kernelPaths = [
                sprintf('%s/Tests/app/AppKernel.php', getcwd()), // bundle test kernel
                sprintf('%s/tests/app/AppKernel.php', getcwd()), // sulu-io/sulu test kernel
            ];
        }

        $found = false;

        foreach ($kernelPaths as $kernelPath) {
            if (file_exists($kernelPath)) {
                $found = true;
                require_once $kernelPath;
                break;
            }
        }

        if (false === $found) {
            throw new \InvalidArgumentException(sprintf(
                'Could not find test kernel in paths "%s"',
                implode('", "', $kernelPaths)
            ));
        }
    }

    /**
     * Shutdown each kernel after the test has finished.
     */
    public function tearDown()
    {
        while ($kernel = array_shift($this->kernelStack)) {
            $kernel->shutdown();
        }
    }

    /**
     * Assert the HTTP status code of a Response.
     *
     * If the response is not as expected we set the assertion message to the
     * body of the response - if it is json-decodable then we pretty print
     * JSON.
     *
     * The $debugLength argument limits the number of lines included from the
     * response body in case of failure.
     *
     * @param int $code
     * @param Response $response
     * @param int $debugLength
     */
    protected function assertHttpStatusCode($code, Response $response, $debugLength = 10)
    {
        $httpCode = $response->getStatusCode();

        $message = null;
        if ($code !== $httpCode) {
            $message = $response->getContent();

            if ($json = json_decode($message, true)) {
                $message = explode(PHP_EOL, json_encode($json, JSON_PRETTY_PRINT));
                $message = implode(PHP_EOL, array_slice($message, 0, $debugLength));
                $message = sprintf(
                    'HTTP status code %s is not expected %s, showing %s lines of the response body: %s',
                    $httpCode,
                    $code,
                    $debugLength,
                    $message
                );
            }
        }

        $this->assertEquals($code, $httpCode, $message);
    }
}
