/* eslint-disable flowtype/require-valid-file-annotation */
import FormStore from '../../stores/FormStore';
import ResourceRequester from '../../../../services/ResourceRequester';

jest.mock('../../../../services/ResourceRequester', () => ({
    get: jest.fn().mockReturnValue({
        then: jest.fn(),
    }),
    put: jest.fn(),
}));

test('Create data object for schema', () => {
    const formStore = new FormStore();
    formStore.changeSchema({
        title: {},
        description: {},
    });

    expect(Object.keys(formStore.data)).toHaveLength(2);
    expect(formStore.data).toEqual({
        title: null,
        description: null,
    });

    formStore.changeSchema({
        text: {},
    });
});

test('Change schema should keep data', () => {
    const formStore = new FormStore();
    formStore.data = {
        title: 'Title',
        slogan: 'Slogan',
    };
    formStore.changeSchema({
        title: {},
        description: {},
    });

    expect(Object.keys(formStore.data)).toHaveLength(3);
    expect(formStore.data).toEqual({
        title: 'Title',
        description: null,
        slogan: 'Slogan',
    });
});

test('Should be marked dirty when value is changed', () => {
    const formStore = new FormStore();
    expect(formStore.dirty).toBe(false);
    formStore.set('test', 'value');

    expect(formStore.data.test).toBe('value');
    expect(formStore.dirty).toBe(true);
});

test('Should load the data with the ResourceRequester', () => {
    ResourceRequester.get.mockReturnValue({
        then: (callback) => {
            callback({value: 'Value'});
        },
    });
    const formStore = new FormStore('snippets', 3);
    expect(ResourceRequester.get).toBeCalledWith('snippets', 3);
    expect(formStore.data).toEqual({value: 'Value'});
});

test('Loading flag should be set to true when loading', () => {
    ResourceRequester.get.mockReturnValue({
        then: function() {},
    });
    const formStore = new FormStore('snippets', 1);
    formStore.loading = false;

    formStore.load();
    expect(formStore.loading).toBe(true);
});

test('Loading flag should be set to false when loading has finished', () => {
    ResourceRequester.get.mockReturnValue({
        then: function(callback) {
            callback();
        },
    });
    const formStore = new FormStore('snippets', 1);
    formStore.loading = true;

    formStore.load();
    expect(formStore.loading).toBe(false);
});

test('Saving flag should be set to true when saving', () => {
    ResourceRequester.put.mockReturnValue({
        then: function() {},
    });
    const formStore = new FormStore('snippets', 1);
    formStore.saving = false;

    formStore.save();
    expect(formStore.saving).toBe(true);
});

test('Saving flag should be set to false when saving has finished', () => {
    const data = {changed: 'later'};
    ResourceRequester.put.mockReturnValue({
        then: function(callback) {
            callback(data);
        },
    });
    const formStore = new FormStore('snippets', 1);
    formStore.saving = true;

    formStore.save();
    expect(formStore.saving).toBe(false);
    expect(formStore.data).toEqual(data);
});
