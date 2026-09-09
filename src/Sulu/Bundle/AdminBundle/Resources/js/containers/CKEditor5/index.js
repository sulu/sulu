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

// The toolbar is built by appending to it, so a higher priority places an item further to the left. The values below
// reproduce the toolbar order Sulu shipped before the text editor configs were introduced.
const PRIORITY_HEADING = 140;
const PRIORITY_BOLD = 130;
const PRIORITY_ITALIC = 120;
const PRIORITY_UNDERLINE = 110;
const PRIORITY_STRIKETHROUGH = 100;
const PRIORITY_SUBSCRIPT = 90;
const PRIORITY_SUPERSCRIPT = 80;
const PRIORITY_BULLETED_LIST = 70;
const PRIORITY_NUMBERED_LIST = 60;
const PRIORITY_LINK = 50;
const PRIORITY_ALIGNMENT = 40;
const PRIORITY_TABLE = 30;
const PRIORITY_CODE = 20;

/**
 * Maps the tags and features of a text editor config to the CKEditor 5 plugins and config implementing them. Anything
 * registered here is only loaded if the config of the edited property enables the given key.
 */
export function registerCKEditor5Plugins() {
    // "br" needs no plugin, the Essentials plugin always provides shift-enter. It is registered so that a config
    // enabling it is not reported as unknown.
    configRegistry.add((config) => config, 'br');

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
    }), HEADING_TAGS, PRIORITY_HEADING);

    pluginRegistry.add(Bold, 'strong');
    configRegistry.add((config) => ({toolbar: [...config.toolbar, 'bold']}), 'strong', PRIORITY_BOLD);

    pluginRegistry.add(Italic, 'em');
    configRegistry.add((config) => ({toolbar: [...config.toolbar, 'italic']}), 'em', PRIORITY_ITALIC);

    pluginRegistry.add(Underline, 'u');
    configRegistry.add((config) => ({toolbar: [...config.toolbar, 'underline']}), 'u', PRIORITY_UNDERLINE);

    pluginRegistry.add(Strikethrough, 's');
    configRegistry.add(
        (config) => ({toolbar: [...config.toolbar, 'strikethrough']}),
        's',
        PRIORITY_STRIKETHROUGH
    );

    pluginRegistry.add(Subscript, 'sub');
    configRegistry.add((config) => ({toolbar: [...config.toolbar, 'subscript']}), 'sub', PRIORITY_SUBSCRIPT);

    pluginRegistry.add(Superscript, 'sup');
    configRegistry.add((config) => ({toolbar: [...config.toolbar, 'superscript']}), 'sup', PRIORITY_SUPERSCRIPT);

    pluginRegistry.add(List, ['ul', 'ol']);
    configRegistry.add(
        (config) => ({toolbar: [...config.toolbar, 'bulletedlist']}),
        'ul',
        PRIORITY_BULLETED_LIST
    );
    configRegistry.add(
        (config) => ({toolbar: [...config.toolbar, 'numberedlist']}),
        'ol',
        PRIORITY_NUMBERED_LIST
    );

    // Both link plugins bind their button to the command of the other one, so they can only be loaded together.
    pluginRegistry.add(ExternalLinkPlugin, 'a');
    pluginRegistry.add(InternalLinkPlugin, 'a');
    configRegistry.add(
        (config) => ({toolbar: [...config.toolbar, 'externalLink', 'internalLink']}),
        'a',
        PRIORITY_LINK
    );

    pluginRegistry.add(Alignment, 'align');
    configRegistry.add((config) => ({toolbar: [...config.toolbar, 'alignment']}), 'align', PRIORITY_ALIGNMENT);

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
    }), 'table', PRIORITY_TABLE);

    pluginRegistry.add(Code, 'code');
    configRegistry.add((config) => ({toolbar: [...config.toolbar, 'code']}), 'code', PRIORITY_CODE);
}

export default CKEditor5;
export {
    configRegistry,
    pluginRegistry,
};
