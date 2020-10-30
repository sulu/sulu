// @flow
import {action, computed, observable} from 'mobx';
import log from 'loglevel';
import type {Webspace} from './types';

class WebspaceStore {
    @observable allWebspaces: Array<Webspace>;

    @action setWebspaces(webspaces: Array<Webspace>) {
        this.allWebspaces = webspaces;
    }

    @computed get grantedWebspaces(): Array<Webspace> {
        return this.allWebspaces.filter((webspace) => {
            return webspace._permissions.view === true;
        });
    }

    hasWebspace(webspaceKey: string): boolean {
        return !!this.allWebspaces.find((webspace) => webspace.key === webspaceKey);
    }

    getWebspace(webspaceKey: string): Webspace {
        const webspace = this.allWebspaces.find((webspace) => webspace.key === webspaceKey);

        if (!webspace) {
            throw new Error('Webspace "' + webspaceKey + '" not found');
        }

        return webspace;
    }

    // @deprecated
    loadWebspaces(): Promise<Array<Webspace>> {
        log.warn(
            'The "loadWebspaces" method is deprecated since 2.1 and will be removed. ' +
            'Use the "grantedWebspaces" property instead.'
        );

        return Promise.resolve(this.grantedWebspaces);
    }

    // @deprecated
    loadWebspace(webspaceKey: string): Promise<Webspace> {
        log.warn(
            'The "loadWebspace" method is deprecated since 2.1 and will be removed. ' +
            'Use the "getWebspace" method instead.'
        );

        return Promise.resolve(this.getWebspace(webspaceKey));
    }
}

export default new WebspaceStore();
