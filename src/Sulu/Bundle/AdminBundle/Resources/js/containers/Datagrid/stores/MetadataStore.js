// @flow
import resourceMetadataStore from '../../../stores/ResourceMetadataStore';
import type {Schema} from '../types';

class MetadataStore {
    getSchema(resourceKey: string): Promise<Schema> {
        return resourceMetadataStore.loadConfiguration(resourceKey)
            .then((configuration) => {
                if (!('datagrid' in configuration)) {
                    throw new Error('There is no "datagrid" configuration for the resourceKey "' + resourceKey + '"');
                }

                return configuration.datagrid;
            });
    }
}

export default new MetadataStore();
