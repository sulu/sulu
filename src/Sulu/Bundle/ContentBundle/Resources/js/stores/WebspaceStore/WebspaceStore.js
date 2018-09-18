// @flow
import {Config, Requester} from 'sulu-admin-bundle/services';
import type {Webspace} from './types';

class WebspaceStore {
    webspacePromise: Promise<Object>;
    allWebspacePromise: Promise<Object>;

    sendRequest(): Promise<Object> {
        if (!this.webspacePromise) {
            this.webspacePromise = Requester.get(Config.endpoints.webspaces);
        }

        return this.webspacePromise;
    }

    sendAllRequest(): Promise<Object> {
        if (!this.allWebspacePromise) {
            this.allWebspacePromise = Requester.get(Config.endpoints.webspaces + '?checkForPermissions=0');
        }

        return this.allWebspacePromise;
    }

    loadWebspaces(): Promise<Array<Webspace>> {
        return this.sendRequest().then((response: Object) => {
            return response._embedded.webspaces;
        });
    }

    loadAllWebspaces(): Promise<Array<Webspace>> {
        return this.sendAllRequest().then((response: Object) => {
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
