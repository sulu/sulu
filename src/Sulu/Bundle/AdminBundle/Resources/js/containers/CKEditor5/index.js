// @flow
import CKEditor5 from './CKEditor5';
import configRegistry from './registries/configRegistry';
import pluginRegistry from './registries/PluginRegistry';
import {InternalLinkTypeOverlay, internalLinkTypeRegistry} from './plugins/InternalLinkPlugin';

export default CKEditor5;
export {
    InternalLinkTypeOverlay,
    configRegistry,
    pluginRegistry,
    internalLinkTypeRegistry,
};
