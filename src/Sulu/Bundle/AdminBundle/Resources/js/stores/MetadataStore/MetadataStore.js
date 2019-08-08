// @flow
import symfonyRouting from 'fos-jsrouting/router';
import {Requester} from '../../services';

class MetadataStore {
    metadataPromises: {[string]: {[string]: Promise<Object>}} = {};

    loadMetadata(type: string, key: string, metadataOptions: Object): Promise<Object> {
        const parameters = {
            type: type,
            key: key,
            metadataOptions: {},
        };
        if (metadataOptions) {
            parameters.metadataOptions = metadataOptions;
        }

        if (!this.metadataPromises[type]) {
            this.metadataPromises[type] = {};
        }

        if (!this.metadataPromises[type][key]) {
            this.metadataPromises[type][key] = Requester.get(
                symfonyRouting.generate('sulu_admin.metadata', parameters)
            );
        }

        return this.metadataPromises[type][key];
    }
}

export default new MetadataStore();
