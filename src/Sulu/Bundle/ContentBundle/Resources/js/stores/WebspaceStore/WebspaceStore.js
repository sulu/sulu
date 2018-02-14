// @flow
import {Requester} from 'sulu-admin-bundle/services';
import type {Webspace} from './types';

class WebspaceStore {
    baseUrl: string = '/admin/api/webspaces';

    webspacePromise: Promise<Object>;

    loadWebspaces(): Promise<Array<Webspace>> {
        if (!this.webspacePromise) {
            this.webspacePromise = Requester.get(this.baseUrl);
        }

        return this.webspacePromise.then((response: Object) => {
            return response._embedded.webspaces;
        });
    }
}

export default new WebspaceStore();
