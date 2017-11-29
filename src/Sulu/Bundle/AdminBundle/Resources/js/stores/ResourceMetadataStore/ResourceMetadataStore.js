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
                section1: {
                    label: 'Section 1',
                    type: 'section',
                    items: {
                        section11: {
                            label: 'Section 1.1',
                            type: 'section',
                            items: {
                                text11: {
                                    label: 'Text 1.1',
                                    type: 'text_line',
                                },
                            },
                        },
                        section12: {
                            label: 'Section 1.2',
                            type: 'section',
                            items: {
                                text12: {
                                    label: 'Text 1.2',
                                    type: 'text_line',
                                },
                            },
                        },
                        section13: {
                            label: 'Section 1.3',
                            type: 'section',
                            items: {
                                text13: {
                                    label: 'Text 1.3',
                                    type: 'text_line',
                                },
                            },
                        },
                    },
                },
                bla: {
                    label: 'Bla',
                    type: 'text_line',
                },
                section2: {
                    label: 'Section 2',
                    type: 'section',
                    items: {
                        text21: {
                            label: 'Text 2.1',
                            type: 'text_line',
                        },
                    },
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
