// @flow
import {initializer} from 'sulu-admin-bundle/services';
import {fieldRegistry} from 'sulu-admin-bundle/containers';
import {formToolbarActionRegistry} from 'sulu-admin-bundle/views';
import {Permissions, RoleAssignments, RolePermissions} from './containers/Form';
import securityContextStore from './stores/SecurityContextStore';
import EnableUserToolbarAction from './views/Form/toolbarActions/EnableUserToolbarAction';
import LockUserToolbarAction from './views/Form/toolbarActions/LockUserToolbarAction';

fieldRegistry.add('permissions', Permissions);
fieldRegistry.add('role_assignments', RoleAssignments);
fieldRegistry.add('role_permissions', RolePermissions);

formToolbarActionRegistry.add('sulu_security.enable_user', EnableUserToolbarAction);
formToolbarActionRegistry.add('sulu_security.lock_user', LockUserToolbarAction);

initializer.addUpdateConfigHook('sulu_security', (config: Object) => {
    securityContextStore.endpoint = config.endpoints.contexts;
    securityContextStore.resourceKeyMapping = config.resourceKeySecurityContextMapping;
});
