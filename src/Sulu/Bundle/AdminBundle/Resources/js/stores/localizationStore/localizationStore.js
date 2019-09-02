// @flow
import {ResourceRequester} from '../../services';
import type {Localization} from './types';

class LocalizationStore {
    localizationPromise: Promise<Object>;

    sendRequest(): Promise<Object> {
        if (!this.localizationPromise) {
            this.localizationPromise = ResourceRequester.getList('localizations');
        }

        return this.localizationPromise;
    }

    loadLocalizations(): Promise<Array<Localization>> {
        return this.sendRequest().then((response: Object) => {
            return response._embedded.localizations;
        });
    }
}

export default new LocalizationStore();
