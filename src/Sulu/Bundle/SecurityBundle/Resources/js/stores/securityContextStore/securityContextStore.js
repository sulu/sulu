// @flow
import log from 'loglevel';
import type {Actions, SecurityContextGroups, Systems} from './types';

class SecurityContextStore {
    securityContexts: Systems;

    // TODO Could be removed by using resourceKey for security as well instead of separate security key
    resourceKeyMapping: {[resourceKey: string]: string};

    getSystems(): Array<string> {
        return Object.keys(this.securityContexts);
    }

    setSecurityContexts(securityContexts: Systems) {
        this.securityContexts = securityContexts;
    }

    getSecurityContextByResourceKey(resourceKey: string) {
        return this.resourceKeyMapping[resourceKey];
    }

    getSecurityContextGroups(system: string): SecurityContextGroups {
        return this.securityContexts[system];
    }

    getAvailableActions(resourceKey: string, system: string = 'Sulu'): Actions {
        if (!this.securityContexts[system]) {
            return [];
        }

        for (const groupKey in this.securityContexts[system]) {
            const group = this.securityContexts[system][groupKey];
            for (const permissionKey in group) {
                if (permissionKey === this.resourceKeyMapping[resourceKey]) {
                    return group[permissionKey];
                }
            }
        }

        return [];
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
