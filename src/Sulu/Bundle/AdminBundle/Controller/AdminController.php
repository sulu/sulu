<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Controller;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\AdminBundle\Admin\JsConfigPool;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorBagInterface;

class AdminController
{
    const TRANSLATION_DOMAIN = 'admin';

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AdminPool
     */
    private $adminPool;

    /**
     * @var JsConfigPool
     */
    private $jsConfigPool;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var ViewHandlerInterface
     */
    private $viewHandler;

    /**
     * @var EngineInterface
     */
    private $engine;

    /**
     * @var LocalizationManagerInterface
     */
    private $localizationManager;

    /**
     * @var TranslatorBagInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var string
     */
    private $adminName;

    /**
     * @var array
     */
    private $locales;

    /**
     * @var string
     */
    private $suluVersion;

    /**
     * @var array
     */
    private $translatedLocales;

    /**
     * @var array
     */
    private $translations;

    /**
     * @var string
     */
    private $fallbackLocale;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        UrlGeneratorInterface $urlGenerator,
        TokenStorageInterface $tokenStorage,
        AdminPool $adminPool,
        JsConfigPool $jsConfigPool,
        SerializerInterface $serializer,
        ViewHandlerInterface $viewHandler,
        EngineInterface $engine,
        LocalizationManagerInterface $localizationManager,
        TranslatorBagInterface $translator,
        $environment,
        $adminName,
        array $locales,
        $suluVersion,
        $translatedLocales,
        $translations,
        $fallbackLocale
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->urlGenerator = $urlGenerator;
        $this->tokenStorage = $tokenStorage;
        $this->adminPool = $adminPool;
        $this->jsConfigPool = $jsConfigPool;
        $this->serializer = $serializer;
        $this->viewHandler = $viewHandler;
        $this->engine = $engine;
        $this->localizationManager = $localizationManager;
        $this->translator = $translator;
        $this->environment = $environment;
        $this->adminName = $adminName;
        $this->locales = $locales;
        $this->suluVersion = $suluVersion;
        $this->translatedLocales = $translatedLocales;
        $this->translations = $translations;
        $this->fallbackLocale = $fallbackLocale;
    }

    /**
     * Renders admin ui.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @deprecated Should be replaced with indexV2Action
     */
    public function indexAction()
    {
        if (!$this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return new RedirectResponse($this->urlGenerator->generate('sulu_admin.login', []));
        }

        $user = $this->tokenStorage->getToken()->getUser();

        // get js config from bundles
        $jsConfig = $this->jsConfigPool->getConfigParams();

        // render template
        if ('dev' === $this->environment) {
            $template = 'SuluAdminBundle:Admin:index.html.twig';
        } else {
            $template = 'SuluAdminBundle:Admin:index.html.dist.twig';
        }

        return $this->engine->renderResponse(
            $template,
            [
                'name' => $this->adminName,
                'locales' => array_keys($this->localizationManager->getLocalizations()),
                'translated_locales' => $this->translatedLocales,
                'translations' => $this->translations,
                'fallback_locale' => $this->fallbackLocale,
                'suluVersion' => $this->suluVersion,
                'user' => $this->serializer->serialize(
                    $user,
                    'array',
                    SerializationContext::create()->setGroups(['frontend'])
                ),
                'config' => $jsConfig,
            ]
        );
    }

    public function indexV2Action()
    {
        return $this->engine->renderResponse('SuluAdminBundle:Admin:main.html.twig');
    }

    /**
     * Returns all the configuration for the admin interface.
     */
    public function configV2Action(): Response
    {
        $view = View::create([
            'routes' => $this->adminPool->getRoutes(),
        ]);
        $view->setFormat('json');

        return $this->viewHandler->handle($view);
    }

    public function translationsAction(Request $request): Response
    {
        $catalogue = $this->translator->getCatalogue($request->query->get('locale'));
        $fallbackCatalogue = $catalogue->getFallbackCatalogue();

        $translations = $catalogue->all(static::TRANSLATION_DOMAIN);
        if ($fallbackCatalogue) {
            $translations = array_replace($fallbackCatalogue->all(static::TRANSLATION_DOMAIN), $translations);
        }

        return new JsonResponse($translations);
    }

    public function resourcesAction($resource): Response
    {
        $response = null;
        switch ($resource) {
        case 'snippets':
            $response = new Response(
                <<<'EOL'
{
    "list": {
        "id": {},
        "title": {},
        "template": {},
        "changed": {},
        "created": {}
    },
    "types": {
        "default": {
            "title": "Default",
            "form": {
                "title": {
                    "label": "Title",
                    "type": "text_line",
                    "required": true
                },
                "description": {
                    "label": "Description",
                    "type": "text_line"
                },
                "media": {
                    "label": "Media",
                    "type": "media_selection"
                },
                "blocks": {
                    "label": "Blocks",
                    "maxOccurs": 5,
                    "minOccurs": 2,
                    "type": "block",
                    "types": {
                        "default": {
                            "title": "Default",
                            "form": {
                                "text": {
                                    "label": "Text",
                                    "type": "text_line",
                                    "required": true
                                }
                            }
                        },
                        "image": {
                            "title": "Image",
                            "form": {
                                "image": {
                                    "label": "Image",
                                    "type": "media_selection"
                                }
                            }
                        }
                    }
                }
            },
            "schema": {
                "required": ["title", "blocks"],
                "properties": {
                    "title": {
                        "type": "string",
                        "minLength": 1
                    },
                    "blocks": {
                        "type": "array",
                        "minItems": 3,
                        "items": {
                            "type": "object",
                            "oneOf": [
                                {
                                    "required": ["text"],
                                    "properties": {
                                        "type": {
                                            "const": "default"
                                        },
                                        "text": {
                                            "type": "string",
                                            "minLength": 1
                                        }
                                    }
                                },
                                {
                                    "required": ["image"],
                                    "properties": {
                                        "type": {
                                            "const": "image"
                                        },
                                        "image": {
                                            "type": "object",
                                            "properties": {
                                                "ids": {
                                                    "type": "array",
                                                    "minItems": 3
                                                }
                                            }
                                        }
                                    }
                                }
                            ]
                        }
                    }
                }
            }
        },
        "footer": {
            "title": "Footer",
            "form": {
                "title": {
                    "label": "Title",
                    "type": "text_line",
                    "required": true
                },
                "description": {
                    "label": "Description",
                    "type": "text_line"
                }
            },
            "schema": {
                "required": ["title"],
                "properties": {
                    "title": {
                        "type": "string",
                        "minLength": 1
                    }
                }
            }
        }
    }
}
EOL
            );
            break;
        case 'contacts':
            $response = new Response(
                <<<'EOL'
{
    "list": {
        "id": {},
        "firstName": {},
        "lastName": {},
        "title": {},
        "fullName": {}
    },
    "form": {
        "formOfAddress": {
            "label": "Form of address",
            "required": true,
            "type": "single_select",
            "size": 3,
            "spaceAfter": 9,
            "options": {
                "default_value": "0",
                "values": {
                    "0": "Herr",
                    "1": "Frau"
                }
            }
        },
        "firstName": {
            "label": "First Name",
            "required": true,
            "type": "text_line",
            "size": 6
        },
        "lastName": {
            "label": "Last Name",
            "required": true,
            "type": "text_line",
            "size": 6
        },
        "salutation": {
            "label": "Salutation",
            "required": false,
            "type": "text_line",
            "size": 12
        }
    },
    "schema": {
        "required": ["formOfAddress", "firstName", "lastName"],
        "properties": {
            "formOfAddress": {
                "enum": ["0", "1"]
            },
            "firstName": {
                "type": "string",
                "minLength": 1
            },
            "lastName": {
                "type": "string",
                "minLength": 1
            }
        }
    }
}
EOL
            );
            break;
        case 'accounts':
            $response = new Response(
                <<<'EOL'
{
    "list": {
        "id": {},
        "name": {},
        "email": {}
    }
}
EOL
            );
            break;
        case 'roles':
            $response = new Response(
                <<<'EOL'
{
    "list": {
        "id": {},
        "name": {},
        "system": {}
    }
}
EOL
            );
            break;
        case 'tags':
            $response = new Response(
                <<<'EOL'
{
    "list": {
        "id": {},
        "name": {}
    }
}
EOL
            );
            break;
        case 'collections':
            $response = new Response(
                <<<'EOL'
{
    "list": {
        "id": {},
        "title": {},
        "objectCount": {}
    },
    "form": {
        "title": {
            "label": "Title",
            "type": "text_line"
        },
        "description": {
            "label": "Description",
            "type": "text_area"
        }
    }
}
EOL
            );
            break;
        case 'media':
            $response = new Response(
                <<<'EOL'
{
    "list": {
        "id": {},
        "size": {},
        "title": {},
        "mimeType": {},
        "thumbnails": {}
    },
    "form": {
        "title": {
            "label": "Title",
            "type": "text_line"
        },
        "description": {
            "label": "Description",
            "type": "text_area"
        },
        "license": {
            "label": "License",
            "type": "section",
            "items": {
                "copyright": {
                    "label": "Copyright information",
                    "type": "text_area"
                }
            }
        }
    }
}
EOL
            );
            break;
        case 'nodes':
            $response = new Response(
                <<<'EOL'
{
    "list": {
        "id": {},
        "title": {}
    }
}
EOL
            );
            break;
        }

        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Returns a array of all bundles.
     *
     * @return JsonResponse
     *
     * @deprecated Will not be needed anymore with the new version of the admin and be removed in 2.0
     */
    public function bundlesAction()
    {
        $admins = [];

        foreach ($this->adminPool->getAdmins() as $admin) {
            $name = $admin->getJsBundleName();
            if (null !== $name) {
                $admins[] = $name;
            }
        }

        return new JsonResponse($admins);
    }

    /**
     * Returns contexts of admin.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function contextsAction(Request $request)
    {
        $contexts = $this->adminPool->getSecurityContexts();
        $mappedContexts = [];
        foreach ($contexts as $system => $sections) {
            foreach ($sections as $section => $contexts) {
                foreach ($contexts as $context => $permissionTypes) {
                    $this->addContext($mappedContexts, $system, $section, $context, $permissionTypes);
                }
            }
        }

        $requestedSystem = $request->get('system');
        $response = (null !== $requestedSystem) ? $mappedContexts[$requestedSystem] : $mappedContexts;

        return new JsonResponse($response);
    }

    /**
     * Returns config for admin.
     *
     * @return JsonResponse
     */
    public function configAction()
    {
        return new JsonResponse($this->jsConfigPool->getConfigParams());
    }

    /**
     * Will transform the different representations of permission types to the same representation and adds it to the
     * passed array.
     *
     * @param array $mappedContexts
     * @param string $system
     * @param string $section
     * @param mixed $context
     * @param mixed $permissionTypes
     */
    private function addContext(array &$mappedContexts, $system, $section, $context, $permissionTypes)
    {
        if (is_array($permissionTypes)) {
            $mappedContexts[$system][$section][$context] = $permissionTypes;
        } else {
            $mappedContexts[$system][$section][$permissionTypes] = [
                PermissionTypes::VIEW,
                PermissionTypes::ADD,
                PermissionTypes::EDIT,
                PermissionTypes::DELETE,
                PermissionTypes::SECURITY,
            ];
        }
    }
}
