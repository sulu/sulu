// @flow
import Requester from '../../services/Requester';

class ResourceMetadataStore {
    // TODO load from server
    baseUrls: {[string]: string} = {
        snippets: '/admin/api/snippets',
        contacts: '/admin/api/contacts',
        accounts: '/admin/api/accounts',
        roles: '/admin/api/roles',
        tags: '/admin/api/tags',
        collections: '/admin/api/collections',
        media: '/admin/api/media',
        pages: '/admin/api/pages',
        products: '/admin/api/products',
        attributes: '/admin/api/attributes',
    };

    configurationPromises: {[string]: Promise<Object>} = {};

    getBaseUrl(key: string) {
        if (!(key in this.baseUrls)) {
            throw new Error('There is no baseUrl for the resourceKey "' + key + '"');
        }
        return this.baseUrls[key];
    }

    loadConfiguration(key: string): Promise<Object> {
        if (!(key in this.configurationPromises)) {
            this.configurationPromises[key] = Requester.get('/admin/resources/' + key);
        }

        return this.configurationPromises[key];
    }
}

export default new ResourceMetadataStore();
