// @flow
import resourceMetadataStore from '../../../stores/ResourceMetadataStore';
import type {Schema} from '../types';

class MetadataStore {
    getSchema(resourceKey: string): Promise<Schema> {
        return resourceMetadataStore.loadConfiguration(resourceKey)
            .then((configuration) => {
                if (!('list' in configuration)) {
                    throw new Error('There are no list configurations for the resourceKey "' + resourceKey + '"');
                }

                return configuration.list;
            });
    }
}

export default new MetadataStore();
