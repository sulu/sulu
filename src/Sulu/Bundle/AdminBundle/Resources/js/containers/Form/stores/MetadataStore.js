// @flow
import resourceMetadataStore from '../../../stores/ResourceMetadataStore';
import type {Schema} from '../../../stores/ResourceStore/types';

class MetadataStore {
    getFields(resourceKey: string): Schema {
        const metadata = resourceMetadataStore.loadConfiguration(resourceKey);
        if (!('form' in metadata)) {
            throw new Error('There are no form metadata for the resourceKey "' + resourceKey + '"');
        }

        return metadata.form;
    }
}

export default new MetadataStore();
