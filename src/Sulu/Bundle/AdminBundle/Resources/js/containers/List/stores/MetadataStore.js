// @flow
import metadataStore from '../../../stores/MetadataStore';
import type {Schema} from '../types';

const LIST_TYPE = 'list';

class MetadataStore {
    getSchema(listKey: string): Promise<Schema> {
        return metadataStore.loadMetadata(LIST_TYPE, listKey);
    }
}

export default new MetadataStore();
