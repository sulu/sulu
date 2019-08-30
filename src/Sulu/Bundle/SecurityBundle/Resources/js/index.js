// @flow
import {initializer} from 'sulu-admin-bundle/services';
import {fieldRegistry} from 'sulu-admin-bundle/containers';
import {formToolbarActionRegistry} from 'sulu-admin-bundle/views';
import {Permissions, RoleAssignments, RolePermissions} from './containers/Form';
import RolePermissionsContainer from './containers/RolePermissions';
import securityContextStore from './stores/securityContextStore';
import EnableUserToolbarAction from './views/Form/toolbarActions/EnableUserToolbarAction';

fieldRegistry.add('permissions', Permissions);
fieldRegistry.add('role_assignments', RoleAssignments);
fieldRegistry.add('role_permissions', RolePermissions);

formToolbarActionRegistry.add('sulu_security.enable_user', EnableUserToolbarAction);

initializer.addUpdateConfigHook('sulu_security', (config: Object) => {
    securityContextStore.endpoint = config.endpoints.contexts;
    // TODO resourceKeyMapping could be removed by using resourceKey instead of separate security context
    securityContextStore.resourceKeyMapping = config.resourceKeySecurityContextMapping;
    RolePermissionsContainer.resourceKeyMapping = config.resourceKeySecurityContextMapping;
});
