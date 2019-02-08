// @flow
import {observable, observable as mockObservable, toJS, when} from 'mobx';
import ResourceFormStore from '../../stores/ResourceFormStore';
import ResourceStore from '../../../../stores/ResourceStore';
import metadataStore from '../../stores/MetadataStore';

jest.mock('../../../../stores/ResourceStore', () => function(resourceKey, id, options) {
    this.id = id;
    this.resourceKey = resourceKey;
    this.save = jest.fn().mockReturnValue(Promise.resolve());
    this.delete = jest.fn().mockReturnValue(Promise.resolve());
    this.set = jest.fn();
    this.setMultiple = jest.fn();
    this.change = jest.fn();
    this.copyFromLocale = jest.fn();
    this.data = mockObservable({});
    this.loading = false;

    if (options) {
        this.locale = options.locale;
    }
});

jest.mock('../../stores/MetadataStore', () => ({}));

beforeEach(() => {
    // $FlowFixMe
    metadataStore.getSchema = jest.fn().mockReturnValue(Promise.resolve({}));
    // $FlowFixMe
    metadataStore.getJsonSchema = jest.fn().mockReturnValue(Promise.resolve({}));
    // $FlowFixMe
    metadataStore.getSchemaTypes = jest.fn().mockReturnValue(Promise.resolve({}));
});

test('Create data object for schema', (done) => {
    const metadata = {
        title: {
            label: 'Title',
            type: 'text_line',
        },
        description: {
            label: 'Description',
            type: 'text_line',
        },
    };

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const metadataPromise = Promise.resolve(metadata);
    metadataStore.getSchema.mockReturnValue(metadataPromise);

    const resourceStore = new ResourceStore('snippets', '1');
    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');
    expect(resourceFormStore.schemaLoading).toEqual(true);

    setTimeout(() => {
        expect(resourceFormStore.schemaLoading).toEqual(false);
        expect(Object.keys(resourceFormStore.data)).toHaveLength(2);
        expect(resourceStore.set).not.toBeCalledWith('template', expect.anything());
        expect(resourceFormStore.data).toEqual({
            title: undefined,
            description: undefined,
        });
        resourceFormStore.destroy();
        done();
    }, 0);
});

test('Evaluate all disabledConditions and visibleConditions for schema', () => {
    const metadata = {
        item1: {
            type: 'text_line',
        },
        item2: {
            type: 'text_line',
            disabledCondition: 'item1 != "item2"',
            visibleCondition: 'item1 == "item2"',
        },
        section: {
            items: {
                item31: {
                    type: 'text_line',
                },
                item32: {
                    type: 'text_line',
                    disabledCondition: 'item1 != "item32"',
                    visibleCondition: 'item1 == "item32"',
                },
            },
            type: 'section',
            disabledCondition: 'item1 != "section"',
            visibleCondition: 'item1 == "section"',
        },
        block: {
            types: {
                text_line: {
                    form: {
                        item41: {
                            type: 'text_line',
                            disabledCondition: 'item1 != "item41"',
                            visibleCondition: 'item1 == "item41"',
                        },
                        item42: {
                            type: 'text_line',
                        },
                    },
                },
            },
        },
    };

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const metadataPromise = Promise.resolve(metadata);
    metadataStore.getSchema.mockReturnValue(metadataPromise);

    const resourceStore = new ResourceStore('snippets', '1');
    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');

    setTimeout(() => {
        const sectionItems1 = resourceFormStore.schema.section.items;
        if (!sectionItems1) {
            throw new Error('Section items should be defined!');
        }
        const blockTypes1 = resourceFormStore.schema.block.types;
        if (!blockTypes1) {
            throw new Error('Block types should be defined!');
        }

        expect(resourceFormStore.schema.item2.disabled).toEqual(true);
        expect(resourceFormStore.schema.item2.visible).toEqual(false);
        expect(sectionItems1.item32.disabled).toEqual(true);
        expect(sectionItems1.item32.visible).toEqual(false);
        expect(resourceFormStore.schema.section.disabled).toEqual(true);
        expect(resourceFormStore.schema.section.visible).toEqual(false);
        expect(blockTypes1.text_line.form.item41.disabled).toEqual(true);
        expect(blockTypes1.text_line.form.item41.visible).toEqual(false);

        resourceStore.data = observable({item1: 'item2'});
        expect(resourceFormStore.schema.item2.disabled).toEqual(true);
        expect(resourceFormStore.schema.item2.visible).toEqual(false);

        resourceFormStore.finishField('/item1');
        const sectionItems2 = resourceFormStore.schema.section.items;
        if (!sectionItems2) {
            throw new Error('Section items should be defined!');
        }
        const blockTypes2 = resourceFormStore.schema.block.types;
        if (!blockTypes2) {
            throw new Error('Block types should be defined!');
        }

        expect(resourceFormStore.schema.item2.disabled).toEqual(false);
        expect(resourceFormStore.schema.item2.visible).toEqual(true);
        expect(sectionItems2.item32.disabled).toEqual(true);
        expect(sectionItems2.item32.visible).toEqual(false);
        expect(resourceFormStore.schema.section.disabled).toEqual(true);
        expect(resourceFormStore.schema.section.visible).toEqual(false);
        expect(blockTypes2.text_line.form.item41.disabled).toEqual(true);
        expect(blockTypes2.text_line.form.item41.visible).toEqual(false);

        resourceStore.data = observable({item1: 'item32'});
        resourceFormStore.finishField('/item1');
        const sectionItems3 = resourceFormStore.schema.section.items;
        if (!sectionItems3) {
            throw new Error('Section items should be defined!');
        }
        const blockTypes3 = resourceFormStore.schema.block.types;
        if (!blockTypes3) {
            throw new Error('Block types should be defined!');
        }

        expect(resourceFormStore.schema.item2.disabled).toEqual(true);
        expect(resourceFormStore.schema.item2.visible).toEqual(false);
        expect(sectionItems3.item32.disabled).toEqual(false);
        expect(sectionItems3.item32.visible).toEqual(true);
        expect(resourceFormStore.schema.section.disabled).toEqual(true);
        expect(resourceFormStore.schema.section.visible).toEqual(false);
        expect(blockTypes3.text_line.form.item41.disabled).toEqual(true);
        expect(blockTypes3.text_line.form.item41.visible).toEqual(false);

        resourceStore.data = observable({item1: 'section'});
        resourceFormStore.finishField('/item1');
        const sectionItems4 = resourceFormStore.schema.section.items;
        if (!sectionItems4) {
            throw new Error('Section items should be defined!');
        }
        const blockTypes4 = resourceFormStore.schema.block.types;
        if (!blockTypes4) {
            throw new Error('Block types should be defined!');
        }

        expect(resourceFormStore.schema.item2.disabled).toEqual(true);
        expect(resourceFormStore.schema.item2.visible).toEqual(false);
        expect(sectionItems4.item32.disabled).toEqual(true);
        expect(sectionItems4.item32.visible).toEqual(false);
        expect(resourceFormStore.schema.section.disabled).toEqual(false);
        expect(resourceFormStore.schema.section.visible).toEqual(true);
        expect(blockTypes4.text_line.form.item41.disabled).toEqual(true);
        expect(blockTypes4.text_line.form.item41.visible).toEqual(false);

        resourceStore.data = observable({item1: 'item41'});
        resourceFormStore.finishField('/item1');
        const sectionItems5 = resourceFormStore.schema.section.items;
        if (!sectionItems5) {
            throw new Error('Section items should be defined!');
        }
        const blockTypes5 = resourceFormStore.schema.block.types;
        if (!blockTypes5) {
            throw new Error('Block types should be defined!');
        }

        expect(resourceFormStore.schema.item2.disabled).toEqual(true);
        expect(resourceFormStore.schema.item2.visible).toEqual(false);
        expect(sectionItems5.item32.disabled).toEqual(true);
        expect(sectionItems5.item32.visible).toEqual(false);
        expect(resourceFormStore.schema.section.disabled).toEqual(true);
        expect(resourceFormStore.schema.section.visible).toEqual(false);
        expect(blockTypes5.text_line.form.item41.disabled).toEqual(false);
        expect(blockTypes5.text_line.form.item41.visible).toEqual(true);

        resourceFormStore.destroy();
    }, 0);
});

test('Evaluate disabledConditions and visibleConditions for schema with locale', (done) => {
    const metadata = {
        item: {
            type: 'text_line',
            disabledCondition: '__locale == "en"',
            visibleCondition: '__locale == "de"',
        },
    };

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const metadataPromise = Promise.resolve(metadata);
    metadataStore.getSchema.mockReturnValue(metadataPromise);

    const resourceStore = new ResourceStore('snippets', '1', {locale: observable.box('en')});
    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');

    setTimeout(() => {
        expect(resourceFormStore.schema.item.disabled).toEqual(true);
        expect(resourceFormStore.schema.item.visible).toEqual(false);
        done();
    }, 0);
});

test('Evaluate disabledConditions and visibleConditions when changing locale', (done) => {
    const metadata = {
        item: {
            type: 'text_line',
            disabledCondition: '__locale == "en"',
            visibleCondition: '__locale == "de"',
        },
    };

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const metadataPromise = Promise.resolve(metadata);
    metadataStore.getSchema.mockReturnValue(metadataPromise);

    const locale = observable.box('en');
    const resourceStore = new ResourceStore('snippets', '1', {locale});
    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');

    setTimeout(() => {
        expect(resourceFormStore.schema.item.disabled).toEqual(true);
        expect(resourceFormStore.schema.item.visible).toEqual(false);

        locale.set('de');
        setTimeout(() => {
            expect(resourceFormStore.schema.item.disabled).toEqual(false);
            expect(resourceFormStore.schema.item.visible).toEqual(true);
            done();
        });
    });
});

test('Read resourceKey from ResourceStore', () => {
    const resourceStore = new ResourceStore('snippets');
    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');

    expect(resourceFormStore.resourceKey).toEqual('snippets');
});

test('Read locale from ResourceStore', () => {
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable.box('en')});
    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');

    expect(resourceFormStore.locale && resourceFormStore.locale.get()).toEqual('en');
});

test('Read id from ResourceStore', () => {
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable.box('en')});
    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');

    expect(resourceFormStore.id).toEqual('1');
});

test('Read saving flag from ResourceStore', () => {
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable.box('en')});
    resourceStore.saving = true;
    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');

    expect(resourceFormStore.saving).toEqual(true);
});

test('Read deleting flag from ResourceStore', () => {
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable.box('en')});
    resourceStore.deleting = true;
    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');

    expect(resourceFormStore.deleting).toEqual(true);
});

test('Read dirty flag from ResourceStore', () => {
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable.box('en')});
    resourceStore.dirty = true;
    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');

    expect(resourceFormStore.dirty).toEqual(true);
});

test('Set dirty flag from ResourceStore', () => {
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable.box('en')});
    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');
    resourceFormStore.dirty = true;

    expect(resourceFormStore.dirty).toEqual(true);
});

test('Set template property of ResourceStore to first type be default', () => {
    const metadata = {};

    const schemaTypesPromise = Promise.resolve({
        type1: {},
        type2: {},
    });
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const metadataPromise = Promise.resolve(metadata);
    metadataStore.getSchema.mockReturnValue(metadataPromise);

    const resourceStore = new ResourceStore('snippets');
    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');

    return Promise.all([schemaTypesPromise, metadataPromise]).then(() => {
        expect(resourceStore.set).toBeCalledWith('template', 'type1');
        resourceFormStore.destroy();
    });
});

test('Set template property of ResourceStore from the loaded data', () => {
    const metadata = {};

    const schemaTypesPromise = Promise.resolve({
        type1: {},
        type2: {},
    });
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const metadataPromise = Promise.resolve(metadata);
    metadataStore.getSchema.mockReturnValue(metadataPromise);

    const resourceStore = new ResourceStore('snippets', '1');
    resourceStore.data = observable({template: 'type2'});
    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');

    return Promise.all([schemaTypesPromise, metadataPromise]).then(() => {
        expect(resourceStore.set).toBeCalledWith('template', 'type2');
        resourceFormStore.destroy();
    });
});

test('Create data object for schema with sections', () => {
    const metadata = {
        section1: {
            label: 'Section 1',
            type: 'section',
            items: {
                item11: {
                    label: 'Item 1.1',
                    type: 'text_line',
                },
                section11: {
                    label: 'Section 1.1',
                    type: 'section',
                },
            },
        },
        section2: {
            label: 'Section 2',
            type: 'section',
            items: {
                item21: {
                    label: 'Item 2.1',
                    type: 'text_line',
                },
            },
        },
    };

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const metadataPromise = Promise.resolve(metadata);
    metadataStore.getSchema.mockReturnValue(metadataPromise);

    const resourceFormStore = new ResourceFormStore(new ResourceStore('snippets', '1'), 'snippets');

    return Promise.all([schemaTypesPromise, metadataPromise]).then(() => {
        expect(resourceFormStore.data).toEqual({
            item11: undefined,
            item21: undefined,
        });
        resourceFormStore.destroy();
    });
});

test('Change schema should keep data', (done) => {
    const metadata = {
        title: {
            label: 'Title',
            type: 'text_line',
        },
        description: {
            label: 'Description',
            type: 'text_line',
        },
    };

    const resourceStore = new ResourceStore('snippets', '1');
    resourceStore.data = observable({
        title: 'Title',
        slogan: 'Slogan',
    });

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const metadataPromise = Promise.resolve(metadata);
    metadataStore.getSchema.mockReturnValue(metadataPromise);

    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');

    setTimeout(() => {
        expect(Object.keys(resourceFormStore.data)).toHaveLength(3);
        expect(resourceFormStore.data).toEqual({
            title: 'Title',
            description: undefined,
            slogan: 'Slogan',
        });
        resourceFormStore.destroy();
        done();
    }, 0);
});

test('Change type should update schema and data', (done) => {
    const schemaTypesPromise = Promise.resolve({});
    const sidebarMetadata = {
        title: {
            label: 'Title',
            type: 'text_line',
        },
        description: {
            label: 'Description',
            type: 'text_line',
        },
    };
    const sidebarPromise = Promise.resolve(sidebarMetadata);
    const jsonSchemaPromise = Promise.resolve({});

    const resourceStore = new ResourceStore('snippets', '1');
    resourceStore.data = observable({
        title: 'Title',
        slogan: 'Slogan',
    });

    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);
    metadataStore.getSchema.mockReturnValue(sidebarPromise);
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);
    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');
    const cachedPathsByTag = resourceFormStore.pathsByTag;

    setTimeout(() => {
        expect(resourceFormStore.rawSchema).toEqual(sidebarMetadata);
        expect(resourceFormStore.pathsByTag).not.toBe(cachedPathsByTag);
        expect(resourceFormStore.data).toEqual({
            title: 'Title',
            description: undefined,
            slogan: 'Slogan',
        });
        resourceFormStore.destroy();
        done();
    }, 0);
});

test('Change type should throw an error if no types are available', () => {
    const promise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(promise);

    const resourceStore = new ResourceStore('snippets', '1');
    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');

    return promise.then(() => {
        expect(() => resourceFormStore.changeType('test')).toThrow(/cannot handle types/);
    });
});

test('types property should be returning types from server', () => {
    const types = {
        sidebar: {key: 'sidebar', title: 'Sidebar'},
        footer: {key: 'footer', title: 'Footer'},
    };
    const promise = Promise.resolve(types);
    metadataStore.getSchemaTypes.mockReturnValue(promise);

    const resourceFormStore = new ResourceFormStore(new ResourceStore('snippets', '1'), 'snippets');
    expect(toJS(resourceFormStore.types)).toEqual({});
    expect(resourceFormStore.typesLoading).toEqual(true);

    return promise.then(() => {
        expect(toJS(resourceFormStore.types)).toEqual(types);
        expect(resourceFormStore.typesLoading).toEqual(false);
        resourceFormStore.destroy();
    });
});

test('Type should be set from response', () => {
    const resourceStore = new ResourceStore('snippets', '1');
    resourceStore.data = observable({
        template: 'sidebar',
    });

    const schemaTypesPromise = Promise.resolve({
        sidebar: {},
    });
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);
    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');

    return schemaTypesPromise.then(() => {
        expect(resourceFormStore.type).toEqual('sidebar');
    });
});

test('Type should not be set from response if types are not supported', () => {
    const resourceStore = new ResourceStore('snippets', '1');
    resourceStore.data = observable({
        template: 'sidebar',
    });

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);
    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');

    return schemaTypesPromise.then(() => {
        expect(resourceFormStore.type).toEqual(undefined);
    });
});

test('Changing type should set the appropriate property in the ResourceStore', (done) => {
    const resourceStore = new ResourceStore('snippets', '1');
    resourceStore.data = observable({
        template: 'sidebar',
    });

    const schemaTypesPromise = Promise.resolve({
        sidebar: {},
        footer: {},
    });
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const metadataPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(metadataPromise);

    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');

    return metadataPromise.then(() => {
        resourceFormStore.changeType('footer');
        expect(resourceFormStore.type).toEqual('footer');
        setTimeout(() => { // The observe command is executed later
            expect(resourceStore.change).toBeCalledWith('template', 'footer');
            done();
        });
    });
});

test('Changing type should throw an exception if types are not supported', () => {
    const resourceStore = new ResourceStore('snippets', '1');

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');

    return schemaTypesPromise.then(() => {
        expect(() => resourceFormStore.changeType('sidebar'))
            .toThrow(/"snippets" handled by this ResourceFormStore cannot handle types/);
    });
});

test('Loading flag should be set to true as long as schema is loading', () => {
    const resourceFormStore = new ResourceFormStore(
        new ResourceStore('snippets', '1', {locale: observable.box()}),
        'snippets'
    );
    resourceFormStore.resourceStore.loading = false;

    expect(resourceFormStore.loading).toBe(true);
    resourceFormStore.destroy();
});

test('Loading flag should be set to true as long as data is loading', () => {
    const resourceFormStore = new ResourceFormStore(
        new ResourceStore('snippets', '1', {locale: observable.box()}),
        'snippets'
    );
    resourceFormStore.resourceStore.loading = true;
    resourceFormStore.schemaLoading = false;

    expect(resourceFormStore.loading).toBe(true);
    resourceFormStore.destroy();
});

test('Loading flag should be set to false after data and schema have been loading', () => {
    const resourceFormStore = new ResourceFormStore(
        new ResourceStore('snippets', '1', {locale: observable.box()}),
        'snippets'
    );
    resourceFormStore.resourceStore.loading = false;
    resourceFormStore.schemaLoading = false;

    expect(resourceFormStore.loading).toBe(false);
    resourceFormStore.destroy();
});

test('Save the store should call the resourceStore save function', () => {
    const resourceFormStore = new ResourceFormStore(
        new ResourceStore('snippets', '3', {locale: observable.box()}),
        'snippets'
    );

    resourceFormStore.save();
    expect(resourceFormStore.resourceStore.save).toBeCalledWith({});
    resourceFormStore.destroy();
});

test('Save the store should call the resourceStore save function with the passed options', () => {
    const resourceFormStore = new ResourceFormStore(
        new ResourceStore('snippets', '3', {locale: observable.box()}),
        'snippets',
        {option1: 'value1', option2: 'value2'}
    );

    resourceFormStore.save({option: 'value'});
    expect(resourceFormStore.resourceStore.save)
        .toBeCalledWith({option: 'value', option1: 'value1', option2: 'value2'});
    resourceFormStore.destroy();
});

test('Save the store should reject if request has failed', (done) => {
    const jsonSchemaPromise = Promise.resolve({});
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const resourceStore = new ResourceStore('snippets', '3');
    const error = {
        text: 'Something failed',
    };
    const errorResponse = {
        json: jest.fn().mockReturnValue(Promise.resolve(error)),
    };
    resourceStore.save.mockReturnValue(Promise.reject(errorResponse));
    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');

    resourceStore.data = observable({
        blocks: [
            {
                text: 'Test',
            },
            {
                text: 'T',
            },
        ],
    });

    when(
        () => !resourceFormStore.schemaLoading,
        (): void => {
            const savePromise = resourceFormStore.save();
            savePromise.catch(() => {
                expect(toJS(resourceFormStore.errors)).toEqual({});
            });

            // $FlowFixMe
            expect(savePromise).rejects.toEqual(error).then(() => done());
        }
    );
});

test('Validate should return true if no errors occured', (done) => {
    const jsonSchemaPromise = Promise.resolve({
        required: ['title'],
    });
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const resourceStore = new ResourceStore('snippets', '3');
    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');

    resourceStore.data = observable({
        title: 'Test',
    });

    when(
        () => !resourceFormStore.schemaLoading,
        (): void => {
            expect(resourceFormStore.validate()).toEqual(true);
            done();
        }
    );
});

test('Validate should return false if errors occured', (done) => {
    const jsonSchemaPromise = Promise.resolve({
        required: ['title'],
    });
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const resourceStore = new ResourceStore('snippets', '3');
    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');

    resourceStore.data = observable({});
    when(
        () => !resourceFormStore.schemaLoading,
        (): void => {
            expect(resourceFormStore.validate()).toEqual(false);
            done();
        }
    );
});

test('Save the store should validate the current data', (done) => {
    const jsonSchemaPromise = Promise.resolve({
        required: ['title', 'blocks'],
        properties: {
            blocks: {
                type: 'array',
                items: {
                    type: 'object',
                    oneOf: [
                        {
                            properties: {
                                text: {
                                    type: 'string',
                                    minLength: 3,
                                },
                            },
                        },
                    ],
                },
            },
        },
    });
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const resourceStore = new ResourceStore('snippets', '3');
    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');

    resourceStore.data = observable({
        blocks: [
            {
                text: 'Test',
            },
            {
                text: 'T',
            },
        ],
    });

    when(
        () => !resourceFormStore.schemaLoading,
        (): void => {
            const savePromise = resourceFormStore.save();
            savePromise.catch(() => {
                expect(toJS(resourceFormStore.errors)).toEqual({
                    title: {
                        keyword: 'required',
                        parameters: {
                            missingProperty: 'title',
                        },
                    },
                    blocks: [
                        undefined,
                        {
                            text: {
                                keyword: 'minLength',
                                parameters: {
                                    limit: 3,
                                },
                            },
                        },
                    ],
                });
            });

            // $FlowFixMe
            expect(savePromise).rejects.toEqual(expect.any(String)).then(() => done());
        }
    );
});

test('Delete should delegate the call to resourceStore', () => {
    const deletePromise = Promise.resolve();
    const resourceStore = new ResourceStore('snippets', 3);
    resourceStore.delete.mockReturnValue(deletePromise);

    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');
    const returnedDeletePromise = resourceFormStore.delete();

    expect(resourceStore.delete).toBeCalledWith({});
    expect(returnedDeletePromise).toBe(deletePromise);
});

test('Delete should delegate the call to resourceStore with options', () => {
    const deletePromise = Promise.resolve();
    const resourceStore = new ResourceStore('snippets', 3);
    resourceStore.delete.mockReturnValue(deletePromise);

    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets', {webspace: 'sulu_io'});
    const returnedDeletePromise = resourceFormStore.delete();

    expect(resourceStore.delete).toBeCalledWith({webspace: 'sulu_io'});
    expect(returnedDeletePromise).toBe(deletePromise);
});

test('Data attribute should return the data from the resourceStore', () => {
    const resourceFormStore = new ResourceFormStore(new ResourceStore('snippets', '3'), 'snippets');
    resourceFormStore.resourceStore.data = observable({
        title: 'Title',
    });

    expect(resourceFormStore.data).toBe(resourceFormStore.resourceStore.data);
    resourceFormStore.destroy();
});

test('Set should be passed to resourceStore', () => {
    const resourceFormStore = new ResourceFormStore(new ResourceStore('snippets', '3'), 'snippets');
    resourceFormStore.set('title', 'Title');

    expect(resourceFormStore.resourceStore.set).toBeCalledWith('title', 'Title');
    resourceFormStore.destroy();
});

test('SetMultiple should be passed to resourceStore', () => {
    const resourceFormStore = new ResourceFormStore(new ResourceStore('snippets', '3'), 'snippets');
    const data = {
        title: 'Title',
        description: 'Description',
    };
    resourceFormStore.setMultiple(data);

    expect(resourceFormStore.resourceStore.setMultiple).toBeCalledWith(data);
    resourceFormStore.destroy();
});

test('Destroying the store should call all the disposers', () => {
    const resourceFormStore = new ResourceFormStore(new ResourceStore('snippets', '2'), 'snippets');
    resourceFormStore.schemaDisposer = jest.fn();
    resourceFormStore.typeDisposer = jest.fn();
    resourceFormStore.updateFieldPathEvaluationsDisposer = jest.fn();

    resourceFormStore.destroy();

    expect(resourceFormStore.schemaDisposer).toBeCalled();
    expect(resourceFormStore.typeDisposer).toBeCalled();
    expect(resourceFormStore.updateFieldPathEvaluationsDisposer).toBeCalled();
});

test('Destroying the store should not fail if no disposers are available', () => {
    const resourceFormStore = new ResourceFormStore(new ResourceStore('snippets', '2'), 'snippets');
    resourceFormStore.schemaDisposer = undefined;
    resourceFormStore.typeDisposer = undefined;

    resourceFormStore.destroy();
});

test('Should return value for property path', () => {
    const resourceStore = new ResourceStore('test', 3);
    resourceStore.data = observable({test: 'value'});

    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');

    expect(resourceFormStore.getValueByPath('/test')).toEqual('value');
});

test('Return all the values for a given tag', () => {
    const resourceStore = new ResourceStore('test', 3);
    resourceStore.data = observable({
        title: 'Value 1',
        description: 'Value 2',
        flag: true,
    });

    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');
    resourceFormStore.rawSchema = {
        title: {
            tags: [
                {name: 'sulu.resource_locator_part'},
            ],
            type: 'text_line',
        },
        description: {
            tags: [
                {name: 'sulu.resource_locator_part'},
            ],
            type: 'text_area',
        },
        flag: {
            type: 'checkbox',
            tags: [
                {name: 'sulu.other'},
            ],
        },
    };

    expect(resourceFormStore.getValuesByTag('sulu.resource_locator_part')).toEqual(['Value 1', 'Value 2']);
});

test('Return all the values for a given tag sorted by priority', () => {
    const resourceStore = new ResourceStore('test', 3);
    resourceStore.data = observable({
        title: 'Value 1',
        description: 'Value 2',
        flag: true,
    });

    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');
    resourceFormStore.rawSchema = {
        title: {
            tags: [
                {name: 'sulu.resource_locator_part', priority: 10},
            ],
            type: 'text_line',
        },
        description: {
            tags: [
                {name: 'sulu.resource_locator_part', priority: 100},
            ],
            type: 'text_area',
        },
        flag: {
            type: 'checkbox',
        },
    };

    expect(resourceFormStore.getValuesByTag('sulu.resource_locator_part')).toEqual(['Value 2', 'Value 1']);
});

test('Return all the values for a given tag within sections', () => {
    const resourceStore = new ResourceStore('test', 3);
    resourceStore.data = observable({
        title: 'Value 1',
        description: 'Value 2',
        flag: true,
        article: 'Value 3',
    });

    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');
    resourceFormStore.rawSchema = {
        highlight: {
            items: {
                title: {
                    tags: [
                        {name: 'sulu.resource_locator_part'},
                    ],
                    type: 'text_line',
                },
                description: {
                    tags: [
                        {name: 'sulu.resource_locator_part'},
                    ],
                    type: 'text_area',
                },
                flag: {
                    type: 'checkbox',
                },
            },
            type: 'section',
        },
        article: {
            tags: [
                {name: 'sulu.resource_locator_part'},
            ],
            type: 'text_area',
        },
    };

    expect(resourceFormStore.getValuesByTag('sulu.resource_locator_part')).toEqual(['Value 1', 'Value 2', 'Value 3']);
});

test('Return all the values for a given tag with empty blocks', () => {
    const resourceStore = new ResourceStore('test', 3);
    resourceStore.data = observable({
        title: 'Value 1',
        description: 'Value 2',
    });

    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');
    resourceFormStore.rawSchema = {
        title: {
            tags: [
                {name: 'sulu.resource_locator_part'},
            ],
            type: 'text_line',
        },
        description: {
            type: 'text_area',
        },
        block: {
            type: 'block',
            types: {
                default: {
                    form: {
                        text: {
                            tags: [
                                {name: 'sulu.resource_locator_part'},
                            ],
                            type: 'text_line',
                        },
                        description: {
                            type: 'text_line',
                        },
                    },
                    title: 'Default',
                },
            },
        },
    };

    expect(resourceFormStore.getValuesByTag('sulu.resource_locator_part')).toEqual(['Value 1']);
});

test('Return all the values for a given tag within blocks', () => {
    const resourceStore = new ResourceStore('test', 3);
    resourceStore.data = observable({
        title: 'Value 1',
        description: 'Value 2',
        block: [
            {type: 'default', text: 'Block 1', description: 'Block Description 1'},
            {type: 'default', text: 'Block 2', description: 'Block Description 2'},
            {type: 'other', text: 'Block 3', description: 'Block Description 2'},
        ],
    });

    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');
    resourceFormStore.rawSchema = {
        title: {
            tags: [
                {name: 'sulu.resource_locator_part'},
            ],
            type: 'text_line',
        },
        description: {
            type: 'text_area',
        },
        block: {
            type: 'block',
            types: {
                default: {
                    form: {
                        text: {
                            tags: [
                                {name: 'sulu.resource_locator_part'},
                            ],
                            type: 'text_line',
                        },
                        description: {
                            type: 'text_line',
                        },
                    },
                    title: 'Default',
                },
                other: {
                    form: {
                        text: {
                            type: 'text_line',
                        },
                    },
                    title: 'Other',
                },
            },
        },
    };

    expect(resourceFormStore.getValuesByTag('sulu.resource_locator_part')).toEqual(['Value 1', 'Block 1', 'Block 2']);
});

test('Return SchemaEntry for given schemaPath', (done) => {
    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const metadataPromise = Promise.resolve(
        {
            title: {
                tags: [
                    {name: 'sulu.resource_locator_part'},
                ],
                type: 'text_line',
            },
            description: {
                type: 'text_area',
            },
            block: {
                type: 'block',
                types: {
                    default: {
                        form: {
                            text: {
                                tags: [
                                    {name: 'sulu.resource_locator_part'},
                                ],
                                type: 'text_line',
                            },
                            description: {
                                type: 'text_line',
                            },
                        },
                        title: 'Default',
                    },
                },
            },
        }
    );
    metadataStore.getSchema.mockReturnValue(metadataPromise);

    const resourceFormStore = new ResourceFormStore(new ResourceStore('test'), 'snippets');

    setTimeout(() => {
        expect(resourceFormStore.getSchemaEntryByPath('/block/types/default/form/text')).toEqual({
            tags: [
                {name: 'sulu.resource_locator_part'},
            ],
            type: 'text_line',
        });
        resourceFormStore.destroy();
        done();
    });
});

test('Remember fields being finished as modified fields and forget about them after saving', () => {
    const resourceFormStore = new ResourceFormStore(new ResourceStore('test'), 'snippets');
    resourceFormStore.rawSchema = {};
    resourceFormStore.finishField('/block/0/text');
    resourceFormStore.finishField('/block/0/text');
    resourceFormStore.finishField('/block/1/text');

    expect(resourceFormStore.isFieldModified('/block/0/text')).toEqual(true);
    expect(resourceFormStore.isFieldModified('/block/1/text')).toEqual(true);
    expect(resourceFormStore.isFieldModified('/block/2/text')).toEqual(false);

    return resourceFormStore.save().then(() => {
        expect(resourceFormStore.isFieldModified('/block/0/text')).toEqual(false);
        expect(resourceFormStore.isFieldModified('/block/1/text')).toEqual(false);
    });
});

test('Set new type after copying from different locale', () => {
    const schemaTypesPromise = Promise.resolve({
        sidebar: {key: 'sidebar', title: 'Sidebar'},
        footer: {key: 'footer', title: 'Footer'},
    });

    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const resourceStore = new ResourceStore('test', 5);
    const resourceFormStore = new ResourceFormStore(resourceStore, 'snippets');

    resourceStore.copyFromLocale.mockReturnValue(Promise.resolve(observable({template: 'sidebar'})));

    return schemaTypesPromise.then(() => {
        resourceFormStore.setType('footer');
        const promise = resourceFormStore.copyFromLocale('de');

        return promise.then(() => {
            expect(resourceFormStore.type).toEqual('sidebar');
        });
    });
});
