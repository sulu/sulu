// @flow
import metadataStore from '../../../stores/metadataStore';
import type {Schema} from '../types';

const LIST_TYPE = 'list';

class MetadataStore {
    getSchema(listKey: string, metadataOptions: ?Object): Promise<Schema> {
        return metadataStore.loadMetadata(LIST_TYPE, listKey, metadataOptions);
    }
}

export default new MetadataStore();
