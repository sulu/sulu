// @flow
import {action, observable, makeObservable} from 'mobx';
import log from 'loglevel';
import type {Localization} from './types';

class LocalizationStore {
    constructor() {
        makeObservable(this);
    }

    @observable localizations: Array<Localization> = [];

    @action setLocalizations(localizations: Array<Localization>) {
        this.localizations = localizations;
    }

    // @deprecated
    loadLocalizations(): Promise<Array<Localization>> {
        log.warn(
            'The "loadLocalizations" method is deprecated since 2.1 and will be removed. ' +
            'Use the "localizations" property instead.'
        );

        return Promise.resolve(this.localizations);
    }
}

export default new LocalizationStore();
