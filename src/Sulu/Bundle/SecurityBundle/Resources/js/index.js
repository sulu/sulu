// @flow
import {initializer} from 'sulu-admin-bundle/services';
import {fieldRegistry} from 'sulu-admin-bundle/containers';
import {Permissions, RoleAssignments, RolePermissions} from './containers/Form';
import securityContextStore from './stores/SecurityContextStore';

fieldRegistry.add('permissions', Permissions);
fieldRegistry.add('role_assignments', RoleAssignments);
fieldRegistry.add('role_permissions', RolePermissions);

initializer.addUpdateConfigHook('sulu_security', (config: Object) => {
    securityContextStore.endpoint = config.endpoints.contexts;
});
