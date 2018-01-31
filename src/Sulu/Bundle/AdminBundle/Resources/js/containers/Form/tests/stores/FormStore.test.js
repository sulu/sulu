// @flow
import {observable, toJS} from 'mobx';
import FormStore from '../../stores/FormStore';
import ResourceStore from '../../../../stores/ResourceStore';
import metadataStore from '../../stores/MetadataStore';

jest.mock('../../../../stores/ResourceStore', () => function(resourceKey) {
    this.resourceKey = resourceKey;
    this.save = jest.fn();
    this.set = jest.fn();
    this.data = {};
    this.loading = false;
});

jest.mock('../../stores/MetadataStore', () => ({
    getSchema: jest.fn().mockReturnValue(Promise.resolve({})),
    getSchemaTypes: jest.fn().mockReturnValue(Promise.resolve({})),
}));

test('Create data object for schema', () => {
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

    const formStore = new FormStore(new ResourceStore('snippets', '1'));
    expect(formStore.schemaLoading).toEqual(true);

    return Promise.all([schemaTypesPromise, metadataPromise]).then(() => {
        expect(formStore.schemaLoading).toEqual(false);
        expect(Object.keys(formStore.data)).toHaveLength(2);
        expect(formStore.data).toEqual({
            title: undefined,
            description: undefined,
        });
        formStore.destroy();
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

    const formStore = new FormStore(new ResourceStore('snippets', '1'));

    return Promise.all([schemaTypesPromise, metadataPromise]).then(() => {
        expect(formStore.data).toEqual({
            item11: undefined,
            item21: undefined,
        });
        formStore.destroy();
    });
});

test('Change schema should keep data', () => {
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
    resourceStore.data = {
        title: 'Title',
        slogan: 'Slogan',
    };

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const metadataPromise = Promise.resolve(metadata);
    metadataStore.getSchema.mockReturnValue(metadataPromise);

    const formStore = new FormStore(resourceStore);

    return Promise.all([schemaTypesPromise, metadataPromise]).then(() => {
        expect(Object.keys(formStore.data)).toHaveLength(3);
        expect(formStore.data).toEqual({
            title: 'Title',
            description: undefined,
            slogan: 'Slogan',
        });
        formStore.destroy();
    });
});

test('Change type should update schema and data', () => {
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

    const resourceStore = new ResourceStore('snippets', '1');
    resourceStore.data = {
        title: 'Title',
        slogan: 'Slogan',
    };

    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);
    metadataStore.getSchema.mockReturnValue(sidebarPromise);
    const formStore = new FormStore(resourceStore);

    return Promise.all([schemaTypesPromise, sidebarPromise]).then(() => {
        expect(formStore.schema).toBe(sidebarMetadata);
        expect(formStore.data).toEqual({
            title: 'Title',
            description: undefined,
            slogan: 'Slogan',
        });
        formStore.destroy();
    });
});

test('Change type should throw an error if no types are available', () => {
    const promise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(promise);

    const resourceStore = new ResourceStore('snippets', '1');
    const formStore = new FormStore(resourceStore);

    return promise.then(() => {
        expect(() => formStore.changeType('test')).toThrow(/cannot handle types/);
    });
});

test('types property should be returning types from server', () => {
    const types = {
        sidebar: {key: 'sidebar', title: 'Sidebar'},
        footer: {key: 'footer', title: 'Footer'},
    };
    const promise = Promise.resolve(types);
    metadataStore.getSchemaTypes.mockReturnValue(promise);

    const formStore = new FormStore(new ResourceStore('snippets', '1'));
    expect(toJS(formStore.types)).toEqual({});
    expect(formStore.typesLoading).toEqual(true);

    return promise.then(() => {
        expect(toJS(formStore.types)).toEqual(types);
        expect(formStore.typesLoading).toEqual(false);
        formStore.destroy();
    });
});

test('Type should be set from response', () => {
    const resourceStore = new ResourceStore('snippets', '1');
    resourceStore.data = {
        template: 'sidebar',
    };

    const schemaTypesPromise = Promise.resolve({
        sidebar: {},
    });
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);
    const formStore = new FormStore(resourceStore);

    return schemaTypesPromise.then(() => {
        expect(formStore.type).toEqual('sidebar');
    });
});

test('Type should not be set from response if types are not supported', () => {
    const resourceStore = new ResourceStore('snippets', '1');
    resourceStore.data = {
        template: 'sidebar',
    };

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);
    const formStore = new FormStore(resourceStore);

    return schemaTypesPromise.then(() => {
        expect(formStore.type).toEqual(undefined);
    });
});

test('Changing type should set the appropriate property in the ResourceStore', () => {
    const resourceStore = new ResourceStore('snippets', '1');
    resourceStore.data = {
        template: 'sidebar',
    };

    const schemaTypesPromise = Promise.resolve({
        sidebar: {},
        footer: {},
    });
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const metadataPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(metadataPromise);

    const formStore = new FormStore(resourceStore);

    return metadataPromise.then(() => {
        formStore.changeType('footer');
        expect(formStore.type).toEqual('footer');
        setTimeout(() => { // The observe command is executed later
            expect(resourceStore.set).toBeCalledWith('template', 'footer');
        });
    });
});

test('Changing type should throw an exception if types are not supported', () => {
    const resourceStore = new ResourceStore('snippets', '1');

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const formStore = new FormStore(resourceStore);

    return schemaTypesPromise.then(() => {
        expect(() => formStore.changeType('sidebar'))
            .toThrow(/"snippets" handled by this FormStore cannot handle types/);
    });
});

test('Loading flag should be set to true as long as schema is loading', () => {
    const formStore = new FormStore(new ResourceStore('snippets', '1', {locale: observable()}));
    formStore.resourceStore.loading = false;

    expect(formStore.loading).toBe(true);
    formStore.destroy();
});

test('Loading flag should be set to true as long as data is loading', () => {
    const formStore = new FormStore(new ResourceStore('snippets', '1', {locale: observable()}));
    formStore.resourceStore.loading = true;
    formStore.schemaLoading = false;

    expect(formStore.loading).toBe(true);
    formStore.destroy();
});

test('Loading flag should be set to false after data and schema have been loading', () => {
    const formStore = new FormStore(new ResourceStore('snippets', '1', {locale: observable()}));
    formStore.resourceStore.loading = false;
    formStore.schemaLoading = false;

    expect(formStore.loading).toBe(false);
    formStore.destroy();
});

test('Save the store should call the resourceStore save function', () => {
    const formStore = new FormStore(new ResourceStore('snippets', '3', {locale: observable()}));

    formStore.save();
    expect(formStore.resourceStore.save).toBeCalled();
    formStore.destroy();
});

test('Data attribute should return the data from the resourceStore', () => {
    const formStore = new FormStore(new ResourceStore('snippets', '3'));
    formStore.resourceStore.data = {
        title: 'Title',
    };

    expect(formStore.data).toBe(formStore.resourceStore.data);
    formStore.destroy();
});

test('Set should be passed to resourceStore', () => {
    const formStore = new FormStore(new ResourceStore('snippets', '3'));
    formStore.set('title', 'Title');

    expect(formStore.resourceStore.set).toBeCalledWith('title', 'Title');
    formStore.destroy();
});

test('Destroying the store should call all the disposers', () => {
    const formStore = new FormStore(new ResourceStore('snippets', '2'));
    formStore.schemaDisposer = jest.fn();
    formStore.typeDisposer = jest.fn();

    formStore.destroy();

    expect(formStore.schemaDisposer).toBeCalled();
    expect(formStore.typeDisposer).toBeCalled();
});

test('Destroying the store should not fail if no disposers are available', () => {
    const formStore = new FormStore(new ResourceStore('snippets', '2'));
    formStore.schemaDisposer = undefined;
    formStore.typeDisposer = undefined;

    formStore.destroy();
});
