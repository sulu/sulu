// @flow
import {Requester} from 'sulu-admin-bundle/services';
import type {Webspace} from './types';

class WebspaceStore {
    baseUrl: string = '/admin/api/webspaces'; // TODO get URL from server

    webspacePromise: Promise<Object>;

    sendRequest(): Promise<Object> {
        if (!this.webspacePromise) {
            this.webspacePromise = Requester.get(this.baseUrl);
        }

        return this.webspacePromise;
    }

    loadWebspaces(): Promise<Array<Webspace>> {
        return this.sendRequest().then((response: Object) => {
            return response._embedded.webspaces;
        });
    }

    loadWebspace(webspaceKey: string): Promise<Webspace> {
        return this.sendRequest().then((response: Object) => {
            for (const webspace of response._embedded.webspaces) {
                if (webspace.key === webspaceKey) {
                    return webspace;
                }
            }

            throw new Error('Webspace "' + webspaceKey + '" not found');
        });
    }
}

export default new WebspaceStore();
