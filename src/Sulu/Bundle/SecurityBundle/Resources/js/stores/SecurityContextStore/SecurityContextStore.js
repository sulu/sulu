// @flow
import {Requester} from 'sulu-admin-bundle/services';
import type {SecurityContextGroups, Systems} from './types';

class SecurityContextStore {
    endpoint: string;
    promise: Promise<Systems>;

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
}

export default new SecurityContextStore();
