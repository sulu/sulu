// @flow
import resourceMetadataStore from '../../../stores/ResourceMetadataStore';
import type {Schema} from '../types';

class MetadataStore {
    getSchema(resourceKey: string): Promise<Schema> {
        return resourceMetadataStore.loadConfiguration(resourceKey)
            .then((configuration) => {
                if (!('form' in configuration)) {
                    throw new Error('There are no form configurations for the resourceKey "' + resourceKey + '"');
                }

                return configuration.form;
            });
    }
}

export default new MetadataStore();
