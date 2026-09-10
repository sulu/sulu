// @flow
import {Alignment} from '@ckeditor/ckeditor5-alignment';
import {Bold, Code, Italic, Strikethrough, Subscript, Superscript, Underline} from '@ckeditor/ckeditor5-basic-styles';
import {Heading} from '@ckeditor/ckeditor5-heading';
import {TextPartLanguage} from '@ckeditor/ckeditor5-language';
import {List} from '@ckeditor/ckeditor5-list';
import {Table, TableToolbar} from '@ckeditor/ckeditor5-table';
import {ContextualBalloon} from '@ckeditor/ckeditor5-ui';
import {registerCKEditor5Plugins} from '../index';
import ExternalLinkPlugin from '../plugins/ExternalLinkPlugin';
import InternalLinkPlugin from '../plugins/InternalLinkPlugin';
import configRegistry from '../registries/configRegistry';
import pluginRegistry from '../registries/pluginRegistry';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

// The tags and features the "default" config Sulu ships enables.
const DEFAULT_CONFIG = {
    enterMode: 'p',
    features: ['align'],
    tags: [
        'h2', 'h3', 'h4', 'h5', 'h6', 'strong', 'em', 'u', 's',
        'sub', 'sup', 'ul', 'ol', 'a', 'table', 'code',
    ],
};

// The tags and features the "mini" config Sulu ships enables.
const MINI_CONFIG = {
    enterMode: 'br',
    features: [],
    tags: ['a', 'strong', 'em'],
};

function buildConfig(textEditorConfig): Object {
    const enabledKeys = [...textEditorConfig.tags, ...textEditorConfig.features];
    const seed: Object = {toolbar: []};

    return configRegistry.getConfigs(enabledKeys).reduce((previousConfig, config) => {
        return {...previousConfig, ...config(previousConfig, textEditorConfig)};
    }, seed);
}

beforeEach(() => {
    pluginRegistry.clear();
    configRegistry.clear();
    registerCKEditor5Plugins();
});

test('Load no plugin and build an empty toolbar for a config without tags', () => {
    const config = {enterMode: 'p', features: [], tags: []};

    expect(pluginRegistry.getPlugins([])).toEqual([]);
    expect(buildConfig(config).toolbar).toEqual([]);
});

test('Load the plugin of a single enabled tag only', () => {
    expect(pluginRegistry.getPlugins(['strong'])).toEqual([Bold]);
    expect(buildConfig({enterMode: 'p', features: [], tags: ['strong']}).toolbar).toEqual(['bold']);
});

test('Load both link plugins for the a tag', () => {
    expect(pluginRegistry.getPlugins(['a'])).toEqual([ExternalLinkPlugin, InternalLinkPlugin]);
    expect(buildConfig({enterMode: 'p', features: [], tags: ['a']}).toolbar)
        .toEqual(['externalLink', 'internalLink']);
});

test('Load the list plugin once for ul and ol together', () => {
    expect(pluginRegistry.getPlugins(['ul', 'ol'])).toEqual([List]);
    expect(buildConfig({enterMode: 'p', features: [], tags: ['ul', 'ol']}).toolbar)
        .toEqual(['bulletedlist', 'numberedlist']);
});

test('Do not offer br as a configurable tag, because no plugin can switch it off', () => {
    // The editor always provides shift-enter, so a "br" switch could never be honoured. It is left out of the
    // vocabulary rather than accepted and ignored.
    expect(pluginRegistry.keys).not.toContain('br');
    expect(configRegistry.keys).not.toContain('br');
    expect(pluginRegistry.getPlugins(['br'])).toEqual([]);
    expect(buildConfig({enterMode: 'br', features: [], tags: ['br']}).toolbar).toEqual([]);
});

test('Load the table plugins and the content toolbar for the table tag', () => {
    expect(pluginRegistry.getPlugins(['table'])).toEqual([Table, TableToolbar]);
    expect(buildConfig({enterMode: 'p', features: [], tags: ['table']}).table).toEqual({
        contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'],
    });
});

test('Load the alignment plugin for the align feature', () => {
    expect(pluginRegistry.getPlugins(['align'])).toEqual([Alignment]);
    expect(buildConfig({enterMode: 'p', features: ['align'], tags: []}).toolbar).toEqual(['alignment']);
});

test('Load all plugins of the default config', () => {
    const enabledKeys = [...DEFAULT_CONFIG.tags, ...DEFAULT_CONFIG.features];

    expect(pluginRegistry.getPlugins(enabledKeys)).toEqual([
        Heading,
        Bold,
        Italic,
        Underline,
        Strikethrough,
        Subscript,
        Superscript,
        List,
        ExternalLinkPlugin,
        InternalLinkPlugin,
        Alignment,
        Table,
        TableToolbar,
        Code,
    ]);
});

test('Build the toolbar of the default config in the order Sulu shipped before', () => {
    expect(buildConfig(DEFAULT_CONFIG).toolbar).toEqual([
        'heading',
        'bold',
        'italic',
        'underline',
        'strikethrough',
        'subscript',
        'superscript',
        'bulletedlist',
        'numberedlist',
        'externalLink',
        'internalLink',
        'alignment',
        'insertTable',
        'code',
    ]);
});

test('Build the heading options from the enabled heading tags only', () => {
    const config = buildConfig({enterMode: 'p', features: [], tags: ['h1', 'h3']});

    expect(config.heading).toEqual({
        options: [
            {class: 'ck-heading_paragraph', model: 'paragraph', title: 'sulu_admin.paragraph'},
            {class: 'ck-heading_heading1', model: 'heading1', title: 'sulu_admin.heading1', view: 'h1'},
            {class: 'ck-heading_heading3', model: 'heading3', title: 'sulu_admin.heading3', view: 'h3'},
        ],
    });
});

test('Append toolbar items of a config registered without a key after the core ones', () => {
    configRegistry.add((config) => ({toolbar: [...config.toolbar, 'fontSize']}));

    expect(buildConfig({enterMode: 'p', features: [], tags: ['strong', 'em']}).toolbar)
        .toEqual(['bold', 'italic', 'fontSize']);
});

test('Apply a config registered without a key to a narrow config as well', () => {
    const plugin = class {};
    pluginRegistry.add(plugin);

    expect(pluginRegistry.getPlugins(['a'])).toContain(plugin);
});

test('Both link plugins require the contextual balloon they use', () => {
    // Before the tags decided which plugins are loaded, the balloon arrived transitively through TableToolbar.
    // A config without the table tag has to keep working, so both plugins declare the dependency themselves.
    expect(ExternalLinkPlugin.requires).toContain(ContextualBalloon);
    expect(InternalLinkPlugin.requires).toContain(ContextualBalloon);
});

test('Every tag and feature the shipped configs enable is claimed by a plugin or config', () => {
    // Guards the three hand-maintained copies of this list against drifting apart: the PHP defaults in
    // SuluAdminExtension::DEFAULT_TEXT_EDITOR_CONFIGS, the registrations in index.js, and MINI_CONFIG/DEFAULT_CONFIG
    // here. A key added on one side without the other makes every editor mount warn, which this test turns red first.
    const claimedKeys = [...pluginRegistry.keys, ...configRegistry.keys];

    for (const {features, tags} of [DEFAULT_CONFIG, MINI_CONFIG]) {
        expect([...tags, ...features].filter((key) => !claimedKeys.includes(key))).toEqual([]);
    }
});

test('Load the text part language plugin only for the lang feature', () => {
    expect(pluginRegistry.getPlugins(['lang'])).toEqual([TextPartLanguage]);
    expect(buildConfig({enterMode: 'p', features: ['lang'], tags: []}).toolbar).toEqual(['textPartLanguage']);
});

test('Do not enable the lang feature in any shipped config', () => {
    // It writes a lang attribute the previous editor could not produce, so enabling it by default would change
    // the markup of every existing field.
    for (const {features} of [DEFAULT_CONFIG, MINI_CONFIG]) {
        expect(features).not.toContain('lang');
    }

    expect(pluginRegistry.getPlugins(DEFAULT_CONFIG.tags)).not.toContain(TextPartLanguage);
});
