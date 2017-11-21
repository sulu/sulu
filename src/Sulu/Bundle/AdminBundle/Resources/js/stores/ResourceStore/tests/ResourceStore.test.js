// @flow
import {observable, when} from 'mobx';
import ResourceStore from '../ResourceStore';
import ResourceRequester from '../../../services/ResourceRequester';

jest.mock('../../../services/ResourceRequester', () => ({
    get: jest.fn(),
    put: jest.fn(),
}));

test('Create data object for schema', () => {
    ResourceRequester.get.mockReturnValue(Promise.resolve());
    const resourceStore = new ResourceStore('snippets', '1');
    resourceStore.changeSchema({
        title: {
            label: 'Title',
            type: 'text_line',
        },
        description: {
            label: 'Description',
            type: 'text_line',
        },
    });

    expect(Object.keys(resourceStore.data)).toHaveLength(2);
    expect(resourceStore.data).toEqual({
        title: null,
        description: null,
    });

    resourceStore.changeSchema({
        text: {
            label: 'Text',
            type: 'text_line',
        },
    });
});

test('Change schema should keep data', () => {
    ResourceRequester.get.mockReturnValue(Promise.resolve());
    const resourceStore = new ResourceStore('snippets', '1');
    resourceStore.data = {
        title: 'Title',
        slogan: 'Slogan',
    };
    resourceStore.changeSchema({
        title: {
            label: 'Title',
            type: 'text_line',
        },
        description: {
            label: 'Description',
            type: 'text_line',
        },
    });

    expect(Object.keys(resourceStore.data)).toHaveLength(3);
    expect(resourceStore.data).toEqual({
        title: 'Title',
        description: null,
        slogan: 'Slogan',
    });
});

test('Should be marked dirty when value is changed', () => {
    ResourceRequester.get.mockReturnValue(Promise.resolve());

    const resourceStore = new ResourceStore('snippets', '1');
    expect(resourceStore.dirty).toBe(false);
    resourceStore.set('test', 'value');

    expect(resourceStore.data.test).toBe('value');
    expect(resourceStore.dirty).toBe(true);
});

test('Should load the data with the ResourceRequester', () => {
    const promise = Promise.resolve({value: 'Value'});
    ResourceRequester.get.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', '3', {locale: observable()});
    resourceStore.setLocale('en');
    expect(ResourceRequester.get).toBeCalledWith('snippets', '3', {locale: 'en'});
    return promise.then(() => {
        expect(resourceStore.data).toEqual({value: 'Value'});
    });
});

test('Should load without locale the data with the ResourceRequester', () => {
    const promise = Promise.resolve({value: 'Value'});
    ResourceRequester.get.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', '3');
    expect(ResourceRequester.get).toBeCalledWith('snippets', '3', {});
    return promise.then(() => {
        expect(resourceStore.data).toEqual({value: 'Value'});
    });
});

test('Should not load the data with the ResourceRequester if locale should be provided but is not', () => {
    const promise = Promise.resolve({value: 'Value'});
    ResourceRequester.get.mockReturnValue(promise);
    new ResourceStore('snippets', '3', {locale: observable()});
    expect(ResourceRequester.get).not.toBeCalled();
});

test('Loading flag should be set to true when loading', () => {
    ResourceRequester.get.mockReturnValue(Promise.resolve());
    const resourceStore = new ResourceStore('snippets', '1');
    resourceStore.loading = false;
    resourceStore.setLocale('en');

    resourceStore.load();
    expect(resourceStore.loading).toBe(true);
});

test('Loading flag should be set to false when loading has finished', () => {
    const promise = Promise.resolve();
    ResourceRequester.get.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', '1');
    resourceStore.setLocale('en');
    resourceStore.loading = true;

    resourceStore.load();
    return promise.then(() => {
        expect(resourceStore.loading).toBe(false);
    });
});

test('Save the store should send a PUT request', () => {
    ResourceRequester.put.mockReturnValue(Promise.resolve());
    const resourceStore = new ResourceStore('snippets', '3', {locale: observable()});
    resourceStore.locale.set('de');
    resourceStore.data = {title: 'Title'};
    resourceStore.dirty = false;

    resourceStore.save();
    expect(ResourceRequester.put).toBeCalledWith('snippets', '3', {title: 'Title'}, {locale: 'de'});
});

test('Save the store should send a PUT request without a locale', () => {
    ResourceRequester.put.mockReturnValue(Promise.resolve());
    const resourceStore = new ResourceStore('snippets', '3', {});
    resourceStore.data = {title: 'Title'};
    resourceStore.dirty = false;

    resourceStore.save();
    expect(ResourceRequester.put).toBeCalledWith('snippets', '3', {title: 'Title'}, {});
});
test('Saving flag should be set to true when saving', () => {
    ResourceRequester.put.mockReturnValue(Promise.resolve());
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable()});
    resourceStore.saving = false;

    resourceStore.save();
    expect(resourceStore.saving).toBe(true);
});

test('Saving and dirty flag should be set and data should be updated to false when saving has finished', () => {
    const data = {changed: 'later'};
    const promise = Promise.resolve(data);
    ResourceRequester.put.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable()});
    resourceStore.saving = true;
    resourceStore.dirty = true;

    resourceStore.save();

    return promise.then(() => {
        expect(resourceStore.saving).toBe(false);
        expect(resourceStore.dirty).toBe(false);
        expect(resourceStore.data).toEqual(data);
    });
});

test('Saving and dirty flag should be set to false when saving has failed', (done) => {
    const promise = Promise.reject(new Error('An error occured!'));
    ResourceRequester.get.mockReturnValue(Promise.resolve({title: 'Title to stay!'}));
    ResourceRequester.put.mockReturnValue(promise);
    const resourceStore = new ResourceStore('snippets', '1', {locale: observable()});
    resourceStore.locale.set('en');
    resourceStore.saving = true;
    resourceStore.dirty = true;

    resourceStore.save();

    return promise.catch(() => {
        when(
            () => !resourceStore.saving,
            () => {
                expect(resourceStore.saving).toBe(false);
                expect(resourceStore.dirty).toBe(true);
                expect(resourceStore.data).toEqual({title: 'Title to stay!'});
                done();
            }
        );
    });
});
