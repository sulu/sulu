// @flow
class ResourceMetadataStore {
    // TODO load from server
    baseUrls: {[string]: string} = {
        snippets: '/admin/api/snippets',
        contacts: '/admin/api/contacts',
        accounts: '/admin/api/accounts',
        roles: '/admin/api/roles',
        tags: '/admin/api/tags',
        collections: '/admin/api/collections',
        collection: '/admin/api/collections/:id',
    };

    // TODO load from server
    configuration: {[string]: Object} = {
        snippets: {
            list: {
                id: {},
                title: {},
                template: {},
                changed: {},
                created: {},
            },
        },
        contacts: {
            list: {
                id: {},
                firstName: {},
                lastName: {},
                title: {},
                fullName: {},
            },
        },
        accounts: {
            list: {
                id: {},
                name: {},
                email: {},
            },
        },
        roles: {
            list: {
                id: {},
                name: {},
                system: {},
            },
        },
        tags: {
            list: {
                id: {},
                name: {},
            },
        },
        // TODO: Remove after API change
        collections: {
            list: {
                id: {},
                title: {},
                objectCount: {},
            },
        },
        media: {
            list: {
                id: {},
                title: {},
            },
        },
    };

    getBaseUrl(key: string) {
        if (!(key in this.baseUrls)) {
            throw new Error('There is no baseUrl for the resourceKey "' + key + '"');
        }
        return this.baseUrls[key];
    }

    loadConfiguration(key: string): Object {
        if (!(key in this.configuration)) {
            throw new Error('There is no configuration for the resourceKey "' + key + '"');
        }
        return this.configuration[key];
    }
}

export default new ResourceMetadataStore();
