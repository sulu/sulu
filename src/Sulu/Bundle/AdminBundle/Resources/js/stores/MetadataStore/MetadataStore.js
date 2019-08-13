// @flow
import symfonyRouting from 'fos-jsrouting/router';
import {buildQueryString} from '../../utils/Request';

const defaultOptions = {
    credentials: 'same-origin',
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
};

class MetadataStore {
    metadataPromises: {[string]: {[string]: ?Promise<Object>}} = {};

    loadMetadata(type: string, key: string, metadataOptions: Object = {}): Promise<Object> {
        const parameters = {
            type: type,
            key: key,
            ...metadataOptions,
        };

        if (!this.metadataPromises[type]) {
            this.metadataPromises[type] = {};
        }
        const keyWithOptions = buildQueryString({
            key: key,
            ...metadataOptions,
        });

        if (!this.metadataPromises[type][keyWithOptions]) {
            const url = symfonyRouting.generate('sulu_admin.metadata', parameters);
            const response = fetch(url, defaultOptions).then((response) => {
                if (!response.ok) {
                    this.metadataPromises[type][keyWithOptions] = undefined;
                    return Promise.reject(response);
                }

                if (response.status === 204) {
                    this.metadataPromises[type][keyWithOptions] = undefined;
                    return Promise.resolve({});
                }

                const cacheControl = response.headers.get('cache-control');
                if (cacheControl && cacheControl.includes('no-store')) {
                    this.metadataPromises[type][keyWithOptions] = undefined;
                }

                return response.json()
                    .then((data) => {
                        return data;
                    });
            });

            this.metadataPromises[type][keyWithOptions] = response;
            return response;
        }

        return this.metadataPromises[type][keyWithOptions];
    }
}

export default new MetadataStore();
