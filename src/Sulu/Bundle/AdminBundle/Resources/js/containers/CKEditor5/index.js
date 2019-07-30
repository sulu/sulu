// @flow
import CKEditor5 from './CKEditor5';
import configHookRegistry from './registries/ConfigHookRegistry';
import pluginRegistry from './registries/PluginRegistry';
import {InternalLinkTypeOverlay, internalLinkTypeRegistry} from './plugins/InternalLinkPlugin';

export default CKEditor5;
export {
    InternalLinkTypeOverlay,
    configHookRegistry,
    pluginRegistry,
    internalLinkTypeRegistry,
};
