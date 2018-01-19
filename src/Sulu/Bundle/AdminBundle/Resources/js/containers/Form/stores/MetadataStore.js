// @flow
import resourceMetadataStore from '../../../stores/ResourceMetadataStore';
import type {Schema, SchemaType} from '../types';

class MetadataStore {
    getSchemaTypes(resourceKey: string): Promise<Array<SchemaType>> {
        return resourceMetadataStore.loadConfiguration(resourceKey)
            .then((configuration) => {
                const {types} = configuration;

                if (!types) {
                    return [];
                }

                return Object.keys(types).map((key) => ({
                    key: key,
                    title: types[key].title || key,
                }));
            });
    }

    getSchema(resourceKey: string, type: ?string): Promise<Schema> {
        return resourceMetadataStore.loadConfiguration(resourceKey)
            .then((configuration) => {
                if (configuration.types && !type) {
                    throw new Error(
                        'The "' + resourceKey + '" configuration requires a type for loading the form configuration'
                    );
                }

                if (!configuration.types && type) {
                    throw new Error(
                        'The "' + resourceKey + '" configuration does not support types,'
                        + ' but a type of "' + type + '" was given'
                    );
                }

                const typeConfiguration = configuration.types ? configuration.types[type] : configuration;

                if (!('form' in typeConfiguration)) {
                    throw new Error('There are no form configurations for the resourceKey "' + resourceKey + '"');
                }

                return typeConfiguration.form;
            });
    }
}

export default new MetadataStore();
