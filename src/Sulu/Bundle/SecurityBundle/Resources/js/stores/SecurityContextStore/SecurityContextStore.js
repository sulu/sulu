// @flow
import {Requester} from 'sulu-admin-bundle/services';
import type {SecurityContextGroups, Systems} from './types';

class SecurityContextStore {
    endpoint: string;
    promise: Promise<Systems>;

    // TODO Could be removed by using resourceKey for security as well instead of separate security key
    resourceKeyMapping: {[resourceKey: string]: string};

    sendRequest(): Promise<Systems> {
        if (!this.promise) {
            this.promise = Requester.get(this.endpoint);
        }

        return this.promise;
    }

    loadSecurityContextGroups(system: string): Promise<SecurityContextGroups> {
        return this.sendRequest().then((response: Systems) => {
            return response[system];
        });
    }

    loadAvailableActions(resourceKey: string) {
        return this.sendRequest().then((systems: Systems) => {
            for (const systemKey in systems) {
                const system = systems[systemKey];
                for (const groupKey in system) {
                    const group = system[groupKey];
                    for (const permissionKey in group) {
                        if (permissionKey === this.resourceKeyMapping[resourceKey]) {
                            return group[permissionKey];
                        }
                    }
                }
            }
        });
    }
}

export default new SecurityContextStore();
