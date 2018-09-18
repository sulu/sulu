// @flow
import {Config, Requester} from 'sulu-admin-bundle/services';
import type {SecurityContextGroups, Systems} from './types';

class SecurityContextsStore {
    promise: Promise<Systems>;

    sendRequest(): Promise<Systems> {
        if (!this.promise) {
            this.promise = Requester.get(Config.endpoints.securityContexts);
        }

        return this.promise;
    }

    loadSecurityContextGroups(system: string): Promise<SecurityContextGroups> {
        return this.sendRequest().then((response: Systems) => {
            return response[system];
        });
    }
}

export default new SecurityContextsStore();
