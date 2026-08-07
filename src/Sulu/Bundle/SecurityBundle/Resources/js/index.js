// @flow
import {initializer} from 'sulu-admin-bundle/services';
import {fieldRegistry} from 'sulu-admin-bundle/containers';
import {formToolbarActionRegistry} from 'sulu-admin-bundle/views';
import {Permissions, RoleAssignments, RolePermissions, TwoFactor} from './containers/Form';
import RolePermissionsContainer from './containers/RolePermissions';
import securityContextStore from './stores/securityContextStore';
import EnableUserToolbarAction from './views/Form/toolbarActions/EnableUserToolbarAction';
import ResetTwoFactorToolbarAction from './views/Form/toolbarActions/ResetTwoFactorToolbarAction';

fieldRegistry.add('permissions', Permissions);
fieldRegistry.add('role_assignments', RoleAssignments);
fieldRegistry.add('role_permissions', RolePermissions);
fieldRegistry.add('two_factor', TwoFactor);

formToolbarActionRegistry.add('sulu_security.enable_user', EnableUserToolbarAction);
formToolbarActionRegistry.add('sulu_security.reset_two_factor', ResetTwoFactorToolbarAction);

initializer.addUpdateConfigHook('sulu_security', (config: Object) => {
    TwoFactor.endpoints = config.endpoints;
    TwoFactor.backupCodesEnabled = config.twoFactorBackupCodesEnabled;
    RolePermissionsContainer.suluSecuritySystem = config.suluSecuritySystem;

    securityContextStore.suluSecuritySystem = config.suluSecuritySystem;
    securityContextStore.securityContexts = config.securityContexts;
    // TODO resourceKeyMapping could be removed by using resourceKey instead of separate security context
    securityContextStore.resourceKeyMapping = config.resourceKeySecurityContextMapping;
});
