// @flow
import metadataStore from '../../../stores/metadataStore';
import type {RawSchema, SchemaTypes} from '../types';

const FORM_TYPE = 'form';

class MetadataStore {
    getSchemaTypes(formKey: string): Promise<SchemaTypes> {
        return metadataStore.loadMetadata(FORM_TYPE, formKey)
            .then((configuration) => {
                const {types} = configuration;

                if (!types) {
                    return {};
                }

                const schemaTypes = {};
                Object.keys(types).forEach((key) => {
                    schemaTypes[key] = {
                        key: key,
                        title: types[key].title || key,
                    };
                });

                return schemaTypes;
            });
    }

    getSchema(formKey: string, type: ?string, metadataOptions: ?Object): Promise<RawSchema> {
        return metadataStore.loadMetadata(FORM_TYPE, formKey, metadataOptions)
            .then((configuration) => {
                const typeConfiguration = this.getTypeConfiguration(configuration, type, formKey);

                if (!typeConfiguration && type) {
                    throw new Error('Type "' + type + '" not found for the formKey "' + formKey + '"');
                }

                if (!('form' in typeConfiguration)) {
                    let errorMessage = 'There is no form schema for the formKey "' + formKey + '"';
                    if (type) {
                        errorMessage += ' for the type "' + type + '"';
                    }

                    throw new Error(errorMessage);
                }

                return typeConfiguration.form;
            });
    }

    getJsonSchema(formKey: string, type: ?string): Promise<Object> {
        return metadataStore.loadMetadata(FORM_TYPE, formKey)
            .then((configuration) => {
                const typeConfiguration = this.getTypeConfiguration(configuration, type, formKey);

                if (!('schema' in typeConfiguration)) {
                    let errorMessage = 'There is no json schema for the formKey "' + formKey + '"';
                    if (type) {
                        errorMessage += ' for the type "' + type + '"';
                    }

                    throw new Error(errorMessage);
                }

                return typeConfiguration.schema;
            });
    }

    getTypeConfiguration(configuration: Object, type: ?string, formKey: string) {
        if (configuration.types && !type) {
            throw new Error(
                'The "' + formKey + '" configuration requires a type for loading the form schema'
            );
        }

        if (!configuration.types && type) {
            throw new Error(
                'The "' + formKey + '" configuration does not support types,'
                + ' but a type of "' + type + '" was given'
            );
        }

        return configuration.types ? configuration.types[type] : configuration;
    }
}

export default new MetadataStore();
