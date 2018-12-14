// @flow
import {bundleReady, initializer} from 'sulu-admin-bundle/services';
import {fieldRegistry} from 'sulu-admin-bundle/containers';
import {Permissions, RoleAssignments} from './containers/Form';
import securityContextStore from './stores/SecurityContextStore';

fieldRegistry.add('permissions', Permissions);
fieldRegistry.add('role_assignments', RoleAssignments);

initializer.addUpdateConfigHook('sulu_security', (config: Object) => {
    securityContextStore.endpoint = config.endpoints.contexts;
});

bundleReady();
