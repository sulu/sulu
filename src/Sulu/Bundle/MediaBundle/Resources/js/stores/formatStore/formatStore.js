// @flow
import {ResourceRequester} from 'sulu-admin-bundle/services';
import {userStore} from 'sulu-admin-bundle/stores';

class FormatStore {
    formatPromise: Promise<Object>;

    sendRequest(): Promise<Object> {
        if (!userStore.user) {
            throw new Error('A user must be logged in to load the webspaces with the correct locale');
        }

        if (!this.formatPromise) {
            this.formatPromise = ResourceRequester.getList('formats', {locale: userStore.user.locale});
        }

        return this.formatPromise;
    }

    loadFormats(): Promise<Array<Object>> {
        return this.sendRequest().then((response: Object) => {
            return response._embedded.formats;
        });
    }
}

export default new FormatStore();
