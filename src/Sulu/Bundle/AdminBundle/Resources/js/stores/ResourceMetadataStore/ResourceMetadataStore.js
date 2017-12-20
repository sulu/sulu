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
                media: {
                    label: 'Media',
                    type: 'media_selection',
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
                title: {
                    label: 'Title',
                    type: 'text_line',
                    size: 6,
                    spaceAfter: 6,
                },
                firstName: {
                    label: 'First Name',
                    type: 'text_line',
                    size: 6,
                },
                lastName: {
                    label: 'Last Name',
                    type: 'text_line',
                    size: 6,
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
            form: {
                title: {
                    label: 'Title',
                    type: 'text_line',
                },
                description: {
                    label: 'Description',
                    type: 'text_area',
                },
                license: {
                    label: 'License',
                    type: 'section',
                    items: {
                        copyright: {
                            label: 'Copyright information',
                            type: 'text_area',
                        },
                    },
                },
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
