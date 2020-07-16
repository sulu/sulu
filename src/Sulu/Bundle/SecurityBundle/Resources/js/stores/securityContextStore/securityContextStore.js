// @flow
import log from 'loglevel';
import type {SecurityContextGroups, Systems} from './types';

class SecurityContextStore {
    securityContexts: Systems;

    // TODO Could be removed by using resourceKey for security as well instead of separate security key
    resourceKeyMapping: {[resourceKey: string]: string};

    setSecurityContexts(securityContexts: Systems) {
        this.securityContexts = securityContexts;
    }

    getSecurityContextGroups(system: string): SecurityContextGroups {
        return this.securityContexts[system];
    }

    getAvailableActions(resourceKey: string) {
        for (const systemKey in this.securityContexts) {
            const system = this.securityContexts[systemKey];
            for (const groupKey in system) {
                const group = system[groupKey];
                for (const permissionKey in group) {
                    if (permissionKey === this.resourceKeyMapping[resourceKey]) {
                        return group[permissionKey];
                    }
                }
            }
        }
    }

    // @deprecated
    loadSecurityContextGroups(system: string): Promise<SecurityContextGroups> {
        log.warn(
            'The "loadSecurityContextGroups" method is deprecated since 2.2 and will be removed. ' +
            'Use the "getSecurityContextGroups" method instead.'
        );

        return Promise.resolve(this.getSecurityContextGroups(system));
    }

    // @deprecated
    loadAvailableActions(resourceKey: string) {
        log.warn(
            'The "loadAvailableActions" method is deprecated since 2.2 and will be removed. ' +
            'Use the "getAvailableActions" method instead.'
        );

        return Promise.resolve(this.getAvailableActions(resourceKey));
    }
}

export default new SecurityContextStore();
