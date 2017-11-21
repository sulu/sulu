// @flow
import resourceMetadataStore from '../../../stores/ResourceMetadataStore';
import type {Schema} from '../types';

class MetadataStore {
    getSchema(resourceKey: string): Schema {
        const metadata = resourceMetadataStore.loadConfiguration(resourceKey);
        if (!('list' in metadata)) {
            throw new Error('There are no list metadata for the resourceKey "' + resourceKey + '"');
        }

        return metadata.list;
    }
}

export default new MetadataStore();
