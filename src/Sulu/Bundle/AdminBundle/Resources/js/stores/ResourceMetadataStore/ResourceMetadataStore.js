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
        media: '/admin/api/media',
        nodes: '/admin/api/nodes', // TODO rename nodes to pages
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
            form: {
                title: {
                    label: 'Title',
                    type: 'text_line',
                },
                slogan: {
                    label: 'Slogan',
                    type: 'text_line',
                },
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
            form: {
                firstName: {
                    label: 'First Name',
                    type: 'text_line',
                },
                lastName: {
                    label: 'Last Name',
                    type: 'text_line',
                },
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
                size: {},
                title: {},
                mimeType: {},
                thumbnails: {},
            },
        },
        nodes: {
            list: {
                id: {},
                title: {},
                template: {},
                changed: {},
                created: {},
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
