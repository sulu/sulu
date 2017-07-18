// @flow
class ResourceMetadataStore {
    baseUrls: {[string]: string} = {
        snippets: '/admin/api/snippets',
        contacts: '/admin/api/contacts',
    };

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
    };

    getBaseUrl(key: string) {
        if (!(key in this.baseUrls)) {
            throw new Error('There is no baseUrl for the resourceKey "' + key + '"');
        }
        return this.baseUrls[key];
    }

    loadConfiguration(key: string): Object {
        if (!(key in this.configuration)) {
            throw new Error('There is no baseUrl for the resourceKey "' + key + '"');
        }
        return this.configuration[key];
    }
}

export default new ResourceMetadataStore();
