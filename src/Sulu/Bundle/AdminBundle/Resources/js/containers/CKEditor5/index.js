// @flow
import {Alignment} from '@ckeditor/ckeditor5-alignment';
import {Bold, Code, Italic, Strikethrough, Subscript, Superscript, Underline} from '@ckeditor/ckeditor5-basic-styles';
import {Heading} from '@ckeditor/ckeditor5-heading';
import {List} from '@ckeditor/ckeditor5-list';
import {Table, TableToolbar} from '@ckeditor/ckeditor5-table';
import {translate} from '../../utils/Translator';
import CKEditor5 from './CKEditor5';
import ExternalLinkPlugin from './plugins/ExternalLinkPlugin';
import InternalLinkPlugin from './plugins/InternalLinkPlugin';
import configRegistry from './registries/configRegistry';
import pluginRegistry from './registries/pluginRegistry';

const HEADING_TAGS = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

// Core registrations are applied before third-party ones, which default to priority 0. Within the core the
// toolbar order follows the registration order below, because Array.prototype.sort is stable.
const PRIORITY_CORE = 10;

/**
 * Maps the tags and features of a text editor config to the CKEditor 5 plugins and config implementing them. Anything
 * registered here is only loaded if the config of the edited property enables the given key.
 */
export function registerCKEditor5Plugins() {
    pluginRegistry.add(Heading, HEADING_TAGS);
    configRegistry.add((config, {tags}) => ({
        heading: {
            options: [
                {
                    model: 'paragraph',
                    title: translate('sulu_admin.paragraph'),
                    class: 'ck-heading_paragraph',
                },
                ...HEADING_TAGS.filter((tag) => tags.includes(tag)).map((tag) => {
                    const level = tag.substring(1);

                    return {
                        model: 'heading' + level,
                        view: tag,
                        title: translate('sulu_admin.heading' + level),
                        class: 'ck-heading_heading' + level,
                    };
                }),
            ],
        },
        toolbar: [...config.toolbar, 'heading'],
    }), HEADING_TAGS, PRIORITY_CORE);

    pluginRegistry.add(Bold, 'strong');
    configRegistry.add((config) => ({toolbar: [...config.toolbar, 'bold']}), 'strong', PRIORITY_CORE);

    pluginRegistry.add(Italic, 'em');
    configRegistry.add((config) => ({toolbar: [...config.toolbar, 'italic']}), 'em', PRIORITY_CORE);

    pluginRegistry.add(Underline, 'u');
    configRegistry.add((config) => ({toolbar: [...config.toolbar, 'underline']}), 'u', PRIORITY_CORE);

    pluginRegistry.add(Strikethrough, 's');
    configRegistry.add(
        (config) => ({toolbar: [...config.toolbar, 'strikethrough']}),
        's',
        PRIORITY_CORE
    );

    pluginRegistry.add(Subscript, 'sub');
    configRegistry.add((config) => ({toolbar: [...config.toolbar, 'subscript']}), 'sub', PRIORITY_CORE);

    pluginRegistry.add(Superscript, 'sup');
    configRegistry.add((config) => ({toolbar: [...config.toolbar, 'superscript']}), 'sup', PRIORITY_CORE);

    pluginRegistry.add(List, ['ul', 'ol']);
    configRegistry.add(
        (config) => ({toolbar: [...config.toolbar, 'bulletedlist']}),
        'ul',
        PRIORITY_CORE
    );
    configRegistry.add(
        (config) => ({toolbar: [...config.toolbar, 'numberedlist']}),
        'ol',
        PRIORITY_CORE
    );

    // Both link plugins bind their button to the command of the other one, so they can only be loaded together.
    pluginRegistry.add(ExternalLinkPlugin, 'a');
    pluginRegistry.add(InternalLinkPlugin, 'a');
    configRegistry.add(
        (config) => ({toolbar: [...config.toolbar, 'externalLink', 'internalLink']}),
        'a',
        PRIORITY_CORE
    );

    pluginRegistry.add(Alignment, 'align');
    configRegistry.add((config) => ({toolbar: [...config.toolbar, 'alignment']}), 'align', PRIORITY_CORE);

    pluginRegistry.add(Table, 'table');
    pluginRegistry.add(TableToolbar, 'table');
    configRegistry.add((config) => ({
        table: {
            contentToolbar: [
                'tableColumn',
                'tableRow',
                'mergeTableCells',
            ],
        },
        toolbar: [...config.toolbar, 'insertTable'],
    }), 'table', PRIORITY_CORE);

    pluginRegistry.add(Code, 'code');
    configRegistry.add((config) => ({toolbar: [...config.toolbar, 'code']}), 'code', PRIORITY_CORE);
}

export default CKEditor5;
export {
    configRegistry,
    pluginRegistry,
};
