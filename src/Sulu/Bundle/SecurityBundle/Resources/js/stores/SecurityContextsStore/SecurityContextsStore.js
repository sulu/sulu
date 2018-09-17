// @flow
import {Config, Requester} from 'sulu-admin-bundle/services';

class SecurityContextsStore {
    promise: Promise<Object>;

    sendRequest(): Promise<Object> {
        if (!this.promise) {
            this.promise = Requester.get(Config.endpoints.securityContexts);
        }

        return this.promise;
    }

    loadSecurityContexts(): Promise<Object> {
        return this.sendRequest().then((response: Object) => {
            return response;
        });
    }
}

export default new SecurityContextsStore();
