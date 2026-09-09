// @flow
import {isArrayLike} from 'mobx';
import textEditorConfigRegistry from './registries/textEditorConfigRegistry';
import type {SchemaOptions} from '../Form/types';
import type {TextEditorConfig} from './types';

const DEFAULT_CONFIG_NAME = 'default';
const HEADING_TAGS = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

/**
 * Resolves the schema options of a text editor property to the config it should be rendered with. The deprecated
 * "formats" and "enter_mode" params still override the config, they are removed in 4.0.
 */
export function resolveTextEditorConfig(options: ?SchemaOptions): TextEditorConfig {
    const configNameValue = options && options.config ? options.config.value : DEFAULT_CONFIG_NAME;

    if (typeof configNameValue !== 'string') {
        throw new Error('The passed "config" must be a string');
    }

    const {enterMode, features, tags} = textEditorConfigRegistry.get(configNameValue);
    const deprecatedEnterMode = readDeprecatedEnterMode(options);
    const deprecatedFormats = readDeprecatedFormats(options);

    return {
        enterMode: deprecatedEnterMode || enterMode,
        features,
        tags: deprecatedFormats
            ? [...tags.filter((tag) => !HEADING_TAGS.includes(tag)), ...deprecatedFormats]
            : tags,
    };
}

function readDeprecatedEnterMode(options: ?SchemaOptions) {
    const enterModeValue = options && options.enter_mode ? options.enter_mode.value : undefined;

    if (enterModeValue !== 'p' && enterModeValue !== 'br') {
        return undefined;
    }

    return enterModeValue;
}

function readDeprecatedFormats(options: ?SchemaOptions) {
    const formatOptionValues = options && options.formats ? options.formats.value : [];

    if (!isArrayLike(formatOptionValues)) {
        throw new Error('The passed "formats" must be an array of strings');
    }

    // $FlowFixMe: flow does not recognize that isArrayLike(value) means that value is an array
    if (!formatOptionValues.length) {
        return undefined;
    }

    // $FlowFixMe: flow does not recognize that isArrayLike(value) means that value is an array
    const formats = formatOptionValues.map((format) => {
        if (typeof format.name !== 'string') {
            throw new Error('The name property of the passed "formats" must be strings!');
        }

        return format.name;
    });

    // The param only ever selected among the heading tags, anything else in it was ignored. Keeping that scope
    // stops a legacy value that happens to name a tag from enabling its plugin on upgrade.
    return formats.filter((format) => HEADING_TAGS.includes(format));
}
