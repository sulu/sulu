<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Sulu\Bundle\MarkupBundle\Listener\MailerListener;
use Sulu\Bundle\MarkupBundle\Listener\MarkupListener;
use Sulu\Bundle\MarkupBundle\Markup\DelegatingTagExtractor;
use Sulu\Bundle\MarkupBundle\Markup\HtmlMarkupParser;
use Sulu\Bundle\MarkupBundle\Markup\HtmlTagExtractor;
use Sulu\Bundle\MarkupBundle\Markup\Link\ExternalLinkProvider;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderPool;
use Sulu\Bundle\MarkupBundle\Markup\LinkTag;
use Sulu\Bundle\MarkupBundle\Markup\MarkupParserInterface;
use Sulu\Bundle\MarkupBundle\Tag\TagRegistry;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    /*
     * Arguments are filled by a compiler pass.
     *
     * @see Sulu\Bundle\MarkupBundle\DependencyInjection\CompilerPass\TagCompilerPass
     */
    $services->set('sulu_markup.tag.registry', TagRegistry::class)
        ->args([[]]);

    $services->set('sulu_markup.parser.html_extractor', HtmlTagExtractor::class)
        ->public()
        ->args(['sulu'])
        ->tag('sulu_markup.parser.html_extractor');

    $services->set('sulu_markup.parser.delegating_html_extractor', DelegatingTagExtractor::class)
        ->args([new TaggedIteratorArgument('sulu_markup.parser.html_extractor')]);

    $services->set('sulu_markup.parser', HtmlMarkupParser::class)
        ->args([
            new Reference('sulu_markup.tag.registry'),
            new Reference('sulu_markup.parser.delegating_html_extractor'),
        ])
        ->tag('sulu_markup.parser', ['type' => 'html']);
    $services->alias(MarkupParserInterface::class, 'sulu_markup.parser');

    $services->set('sulu_markup.response_listener', MarkupListener::class)
        ->args([new TaggedIteratorArgument('sulu_markup.parser', indexAttribute: 'type')])
        ->tag('kernel.event_subscriber');

    $services->set('sulu_markup.mailer_listener', MailerListener::class)
        ->args([
            new Reference(MarkupParserInterface::class),
            new Reference(RequestStack::class),
            '%kernel.default_locale%',
        ])
        ->tag('kernel.event_subscriber');

    $services->set('sulu_markup.link_tag.provider_pool', LinkProviderPool::class)
        ->args([new TaggedIteratorArgument('sulu.link.provider', indexAttribute: 'alias')]);

    $services->set('sulu_markup.link_tag', LinkTag::class)
        ->args([
            new Reference('sulu_markup.link_tag.provider_pool'),
            new Expression('container.hasParameter(\'sulu.preview\') ? parameter(\'sulu.preview\') : false'),
            new Reference('url_helper'),
            '%sulu_markup.link_tag.provider_attribute%',
        ])
        ->tag('sulu_markup.tag', ['tag' => 'link', 'type' => 'html']);

    $services->set('sulu_markup.external_link_provider', ExternalLinkProvider::class)
        ->args([new Reference(TranslatorInterface::class)])
        ->tag('sulu.link.provider', ['alias' => 'external']);
};
