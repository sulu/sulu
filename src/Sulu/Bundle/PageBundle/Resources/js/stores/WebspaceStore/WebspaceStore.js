// @flow
import {ResourceRequester} from 'sulu-admin-bundle/services';
import {userStore} from 'sulu-admin-bundle/stores';
import type {Webspace} from './types';

class WebspaceStore {
    webspacePromise: ?Promise<Object>;

    clear() {
        this.webspacePromise = undefined;
    }

    sendRequest(): Promise<Object> {
        if (!userStore.user) {
            throw new Error('A user must be logged in to load the webspaces with the correct locale');
        }

        if (!this.webspacePromise) {
            this.webspacePromise = ResourceRequester.getList('webspaces', {locale: userStore.user.locale});
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
