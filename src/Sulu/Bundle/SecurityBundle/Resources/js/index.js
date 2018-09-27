// @flow
import {bundleReady, initializer} from 'sulu-admin-bundle/services';
import {fieldRegistry} from 'sulu-admin-bundle/containers';
import Permissions from './containers/Form/fields/Permissions';
import securityContextStore from './stores/SecurityContextStore';

fieldRegistry.add('permissions', Permissions);

initializer.addUpdateConfigHook('sulu_security', (config: Object) => {
    securityContextStore.endpoint = config.routes.contexts;
});

bundleReady();
