/* eslint-disable flowtype/require-valid-file-annotation */
import {when} from 'mobx';
import FormStore from '../../stores/FormStore';
import ResourceRequester from '../../../../services/ResourceRequester';

jest.mock('../../../../services/ResourceRequester', () => ({
    get: jest.fn(),
    put: jest.fn(),
}));

test('Create data object for schema', () => {
    ResourceRequester.get.mockReturnValue(Promise.resolve());
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
    ResourceRequester.get.mockReturnValue(Promise.resolve());
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
    ResourceRequester.get.mockReturnValue(Promise.resolve());

    const formStore = new FormStore();
    expect(formStore.dirty).toBe(false);
    formStore.set('test', 'value');

    expect(formStore.data.test).toBe('value');
    expect(formStore.dirty).toBe(true);
});

test('Should load the data with the ResourceRequester', () => {
    const promise = Promise.resolve({value: 'Value'});
    ResourceRequester.get.mockReturnValue(promise);
    const formStore = new FormStore('snippets', 3);
    formStore.setLocale('en');
    expect(ResourceRequester.get).toBeCalledWith('snippets', 3, {locale: 'en'});
    return promise.then(() => {
        expect(formStore.data).toEqual({value: 'Value'});
    });
});

test('Loading flag should be set to true when loading', () => {
    ResourceRequester.get.mockReturnValue(Promise.resolve());
    const formStore = new FormStore('snippets', 1);
    formStore.loading = false;
    formStore.setLocale('en');

    formStore.load();
    expect(formStore.loading).toBe(true);
});

test('Loading flag should be set to false when loading has finished', () => {
    const promise = Promise.resolve();
    ResourceRequester.get.mockReturnValue(promise);
    const formStore = new FormStore('snippets', 1);
    formStore.setLocale('en');
    formStore.loading = true;

    formStore.load();
    return promise.then(() => {
        expect(formStore.loading).toBe(false);
    });
});

test('Save the store should send a PUT request', () => {
    ResourceRequester.put.mockReturnValue(Promise.resolve());
    const formStore = new FormStore('snippets', 3);
    formStore.locale.set('de');
    formStore.data = {title: 'Title'};
    formStore.dirty = false;

    formStore.save();
    expect(ResourceRequester.put).toBeCalledWith('snippets', 3, {title: 'Title'}, {locale: 'de'});
});

test('Saving flag should be set to true when saving', () => {
    ResourceRequester.put.mockReturnValue(Promise.resolve());
    const formStore = new FormStore('snippets', 1);
    formStore.saving = false;

    formStore.save();
    expect(formStore.saving).toBe(true);
});

test('Saving and dirty flag should be set and data should be updated to false when saving has finished', () => {
    const data = {changed: 'later'};
    const promise = Promise.resolve(data);
    ResourceRequester.put.mockReturnValue(promise);
    const formStore = new FormStore('snippets', 1);
    formStore.saving = true;
    formStore.dirty = true;

    formStore.save();

    return promise.then(() => {
        expect(formStore.saving).toBe(false);
        expect(formStore.dirty).toBe(false);
        expect(formStore.data).toEqual(data);
    });
});

test('Saving and dirty flag should be set to false when saving has failed', (done) => {
    const promise = Promise.reject(new Error('An error occured!'));
    ResourceRequester.get.mockReturnValue(Promise.resolve({title: 'Title to stay!'}));
    ResourceRequester.put.mockReturnValue(promise);
    const formStore = new FormStore('snippets', 1);
    formStore.locale.set('en');
    formStore.saving = true;
    formStore.dirty = true;

    formStore.save();

    return promise.catch(() => {
        when(
            () => !formStore.saving,
            () => {
                expect(formStore.saving).toBe(false);
                expect(formStore.dirty).toBe(true);
                expect(formStore.data).toEqual({title: 'Title to stay!'});
                done();
            }
        );
    });
});
