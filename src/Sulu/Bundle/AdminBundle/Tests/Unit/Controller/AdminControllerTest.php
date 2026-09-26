<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Controller;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use JMS\Serializer\SerializerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\AdminBundle\Controller\AdminController;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Twig\Environment;

class AdminControllerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<UrlGeneratorInterface>
     */
    private $urlGenerator;

    /**
     * @var ObjectProphecy<TokenStorageInterface>
     */
    private $tokenStorage;

    /**
     * @var ObjectProphecy<TokenInterface>
     */
    private $token;

    /**
     * @var ObjectProphecy<User>
     */
    private $user;

    /**
     * @var ObjectProphecy<AdminPool>
     */
    private $adminPool;

    /**
     * @var ObjectProphecy<SerializerInterface>
     */
    private $serializer;

    /**
     * @var ObjectProphecy<ViewHandlerInterface>
     */
    private $viewHandler;

    /**
     * @var ObjectProphecy<Environment>
     */
    private $engine;

    /**
     * @var ObjectProphecy<TranslatorBagInterface>
     */
    private $translatorBag;

    private ContainerInterface $metadataProviderContainer;

    /**
     * @var string
     */
    private $environment = 'prod';

    /**
     * @var string
     */
    private $suluVersion = '2.0.0-RC1';

    /**
     * @var string
     */
    private $appVersion = '666';

    /**
     * @var array
     */
    private $locales = ['de', 'en'];

    /**
     * @var array
     */
    private $translations = ['de', 'en'];

    /**
     * @var string
     */
    private $fallbackLocale = 'de';

    /**
     * @var AdminController
     */
    private $adminController;

    public function setUp(): void
    {
        parent::setUp();

        $this->urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $this->token = $this->prophesize(TokenInterface::class);
        $this->user = $this->prophesize(User::class);
        $this->adminPool = $this->prophesize(AdminPool::class);
        $this->serializer = $this->prophesize(SerializerInterface::class);
        $this->viewHandler = $this->prophesize(ViewHandlerInterface::class);
        $this->engine = $this->prophesize(Environment::class);
        $this->translatorBag = $this->prophesize(TranslatorBagInterface::class);

        $this->metadataProviderContainer = new Container();
        $metadataProviderRegistry = new MetadataProviderRegistry($this->metadataProviderContainer);
        $this->tokenStorage->getToken()->willReturn($this->token->reveal());
        $this->token->getUser()->willReturn($this->user->reveal());

        $this->adminController = new AdminController(
            $this->urlGenerator->reveal(),
            $this->tokenStorage->reveal(),
            $this->adminPool->reveal(),
            $this->serializer->reveal(),
            $this->viewHandler->reveal(),
            $this->engine->reveal(),
            $this->translatorBag->reveal(),
            $metadataProviderRegistry,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $this->environment,
            $this->suluVersion,
            $this->appVersion,
            null,
            $this->locales,
            $this->translations,
            $this->fallbackLocale,
            null,
            null,
        );
    }

    public function testConfigAction(): void
    {
        $this->user->getLocale()->willReturn('en');

        $contact = $this->prophesize(ContactInterface::class);
        $contact->getId()->willReturn(5);

        $admin1 = $this->prophesize(Admin::class);
        $admin1Config = ['test1' => 'value1'];
        $admin1->getConfig()->willReturn($admin1Config);
        $admin1->getConfigKey()->willReturn('sulu_admin');

        $admin2 = $this->prophesize(Admin::class);
        $admin2Config = ['test2' => 'value2'];
        $admin2->getConfig()->willReturn($admin2Config);
        $admin2->getConfigKey()->willReturn('admin2');

        $admin3 = $this->prophesize(Admin::class);
        $admin3->getConfig()->shouldBeCalled();
        $admin3->getConfigKey()->shouldBeCalled();

        $this->adminPool->getAdmins()->willReturn([$admin1, $admin2, $admin3]);

        $this->viewHandler->handle(
            Argument::that(
                function(View $view) {
                    /** @var array<string, array<string, string>> $data */
                    $data = $view->getData();

                    return 'json' === $view->getFormat()
                        && ['test1' => 'value1'] === $data['sulu_admin']
                        && ['test2' => 'value2'] === $data['admin2'];
                }
            )
        )->shouldBeCalled()->willReturn(new Response());

        $this->adminController->configAction();
    }

    public function testMetadataAction(): void
    {
        $form = new FormMetadata();

        $this->user->getLocale()->willReturn('en');

        $metadataProvider = $this->prophesize(MetadataProviderInterface::class);
        $metadataProvider->getMetadata('pages', 'en', [])->willReturn($form);
        $this->metadataProviderContainer->set('form', $metadataProvider->reveal());

        $this->viewHandler->handle(Argument::that(function(View $view) use ($form) {
            return $form === $view->getData();
        }))->shouldBeCalled()->willReturn(new Response());

        $this->adminController->metadataAction('form', 'pages', new Request());
    }

    public function testMetadataActionWithOptions(): void
    {
        $form = new FormMetadata();

        $this->user->getLocale()->willReturn('en');

        $metadataProvider = $this->prophesize(MetadataProviderInterface::class);
        $metadataProvider->getMetadata('pages', 'en', ['id' => 1])->willReturn($form);
        $this->metadataProviderContainer->set('form', $metadataProvider->reveal());

        $this->viewHandler->handle(Argument::that(function(View $view) use ($form) {
            return $form === $view->getData();
        }))->shouldBeCalled()->willReturn(new Response());

        $request = new Request();
        $request->query->add(['id' => 1]);
        $this->adminController->metadataAction('form', 'pages', $request);
    }

    public static function provideTranslationsAction()
    {
        return [
            [
                'en',
                ['save' => 'Save'],
                [],
                '{"save":"Save"}',
            ],
            [
                'de',
                ['save' => 'Speichern'],
                [],
                '{"save":"Speichern"}',
            ],
            [
                'de',
                ['save' => 'Speichern'],
                ['save' => 'Save', 'delete' => 'Delete'],
                '{"save":"Speichern","delete":"Delete"}',
            ],
            [
                'bg',
                [],
                [],
                '{}',
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideTranslationsAction')]
    public function testTranslationsAction($locale, $translations, $fallbackTranslations, $resultTranslations): void
    {
        $request = new Request(['locale' => $locale]);

        $contact = $this->prophesize(ContactInterface::class);
        $contact->getId()->willReturn(5);

        $this->user->getContact()->willReturn($contact->reveal());
        $this->user->getLocale()->willReturn('en');

        $catalogue = $this->prophesize(MessageCatalogueInterface::class);
        $catalogue->all('admin')->willReturn($translations);
        $fallbackCatalogue = $this->prophesize(MessageCatalogueInterface::class);
        $fallbackCatalogue->all('admin')->willReturn($fallbackTranslations);
        $catalogue->getFallbackCatalogue()->willReturn($fallbackCatalogue);
        $this->translatorBag->getCatalogue($locale)->willReturn($catalogue->reveal());

        $response = $this->adminController->translationsAction($request);
        $this->assertEquals($resultTranslations, $response->getContent());
    }

    public function testTranslationActionWithoutFallback(): void
    {
        $request = new Request(['locale' => 'en']);

        $contact = $this->prophesize(ContactInterface::class);
        $contact->getId()->willReturn(5);

        $this->user->getContact()->willReturn($contact->reveal());
        $this->user->getLocale()->willReturn('en');

        $catalogue = $this->prophesize(MessageCatalogueInterface::class);
        $catalogue->all('admin')->willReturn(['save' => 'Save']);
        $catalogue->getFallbackCatalogue()->willReturn(null);
        $this->translatorBag->getCatalogue('en')->willReturn($catalogue->reveal());

        $response = $this->adminController->translationsAction($request);
        $this->assertEquals(['save' => 'Save'], \json_decode($response->getContent(), true));
    }
}
