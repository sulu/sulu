// @flow
import {Requester} from '../../services';

class MetadataStore {
    endpoint: string;
    metadataPromises: {[string]: {[string]: Promise<Object>}} = {};

    loadMetadata(type: string, key: string): Promise<Object> {
        if (!this.metadataPromises[type]) {
            this.metadataPromises[type] = {};
        }

        if (!this.metadataPromises[type][key]) {
            this.metadataPromises[type][key] = Requester.get(
                this.endpoint
                    .replace(':type', type)
                    .replace(':key', key)
            );
        }

        return this.metadataPromises[type][key];
    }
}

export default new MetadataStore();
