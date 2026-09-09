// @flow
import {Alignment} from '@ckeditor/ckeditor5-alignment';
import {Bold, Code, Italic, Strikethrough, Subscript, Superscript, Underline} from '@ckeditor/ckeditor5-basic-styles';
import {Heading} from '@ckeditor/ckeditor5-heading';
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
        'br', 'h2', 'h3', 'h4', 'h5', 'h6', 'strong', 'em', 'u', 's',
        'sub', 'sup', 'ul', 'ol', 'a', 'table', 'code',
    ],
};

function buildConfig(textEditorConfig) {
    const enabledKeys = [...textEditorConfig.tags, ...textEditorConfig.features];

    return configRegistry.getConfigs(enabledKeys).reduce((previousConfig, config) => {
        return {...previousConfig, ...config(previousConfig, textEditorConfig)};
    }, {toolbar: []});
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

test('Load no plugin for the br tag but claim the key so it is not reported as unknown', () => {
    expect(pluginRegistry.getPlugins(['br'])).toEqual([]);
    expect(configRegistry.keys).toContain('br');
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
