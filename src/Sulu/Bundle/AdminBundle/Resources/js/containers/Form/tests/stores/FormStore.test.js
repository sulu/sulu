// @flow
import {observable} from 'mobx';
import FormStore from '../../stores/FormStore';
import ResourceStore from '../../../../stores/ResourceStore';
import metadataStore from '../../stores/MetadataStore';

jest.mock('../../../../stores/ResourceStore', () => function() {
    this.save = jest.fn();
    this.set = jest.fn();
});

jest.mock('../../stores/MetadataStore', () => ({
    getSchema: jest.fn(),
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
    const promise = Promise.resolve(metadata);
    metadataStore.getSchema.mockReturnValue(promise);
    const formStore = new FormStore(new ResourceStore('snippets', '1'));

    return promise.then(() => {
        expect(Object.keys(formStore.data)).toHaveLength(2);
        expect(formStore.data).toEqual({
            title: null,
            description: null,
        });
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
    const promise = Promise.resolve(metadata);
    metadataStore.getSchema.mockReturnValue(promise);

    const formStore = new FormStore(new ResourceStore('snippets', '1'));

    return promise.then(() => {
        expect(formStore.data).toEqual({
            item11: null,
            item21: null,
        });
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

    const promise = Promise.resolve(metadata);
    metadataStore.getSchema.mockReturnValue(promise);
    const formStore = new FormStore(resourceStore);

    return promise.then(() => {
        expect(Object.keys(formStore.data)).toHaveLength(3);
        expect(formStore.data).toEqual({
            title: 'Title',
            description: null,
            slogan: 'Slogan',
        });
    });
});

test('Loading flag should be set to true as long as schema is loading', () => {
    const formStore = new FormStore(new ResourceStore('snippets', '1', {locale: observable()}));
    formStore.resourceStore.loading = false;

    expect(formStore.loading).toBe(true);
});

test('Loading flag should be set to true as long as data is loading', () => {
    const formStore = new FormStore(new ResourceStore('snippets', '1', {locale: observable()}));
    formStore.resourceStore.loading = true;
    formStore.schemaLoading = false;

    expect(formStore.loading).toBe(true);
});

test('Loading flag should be set to false after data and schema have been loading', () => {
    const formStore = new FormStore(new ResourceStore('snippets', '1', {locale: observable()}));
    formStore.resourceStore.loading = false;
    formStore.schemaLoading = false;

    expect(formStore.loading).toBe(false);
});

test('Save the store should call the resourceStore save function', () => {
    const formStore = new FormStore(new ResourceStore('snippets', '3', {locale: observable()}));

    formStore.save();
    expect(formStore.resourceStore.save).toBeCalled();
});

test('Data attribute should return the data from the resourceStore', () => {
    const formStore = new FormStore(new ResourceStore('snippets', '3'));
    formStore.resourceStore.data = {
        title: 'Title',
    };

    expect(formStore.data).toBe(formStore.resourceStore.data);
});

test('Set should be passed to resourceStore', () => {
    const formStore = new FormStore(new ResourceStore('snippets', '3'));
    formStore.set('title', 'Title');

    expect(formStore.resourceStore.set).toBeCalledWith('title', 'Title');
});
