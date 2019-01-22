// @flow
import metadataStore from '../../../stores/MetadataStore';
import type {Schema} from '../types';

const DATAGRID_TYPE = 'datagrid';

class MetadataStore {
    getSchema(datagridKey: string): Promise<Schema> {
        return metadataStore.loadMetadata(DATAGRID_TYPE, datagridKey);
    }
}

export default new MetadataStore();
