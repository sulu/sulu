// @flow
import {observable} from 'mobx';
import ResourceStore from '../../../stores/ResourceStore';
import FormInspector from '../FormInspector';
import FormStore from '../stores/FormStore';

jest.mock('../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, options) {
    this.resourceKey = resourceKey;
    this.id = id;

    if (options) {
        this.locale = options.locale;
    }
}));

jest.mock('../stores/FormStore', () => jest.fn(function(resourceStore) {
    this.resourceKey = resourceStore.resourceKey;
    this.id = resourceStore.id;
    this.locale = resourceStore.locale;
    this.data = resourceStore.data;
    this.schema = {};
    this.getValueByPath = jest.fn();
    this.getValuesByTag = jest.fn();
}));

test('Should return the resourceKey from the FormStore', () => {
    const formStore = new FormStore(new ResourceStore('test'));
    const formInspector = new FormInspector(formStore);

    expect(formInspector.resourceKey).toEqual('test');
});

test('Should return the locale from the FormStore', () => {
    const formStore = new FormStore(new ResourceStore('test', 1, {locale: observable.box('de')}));
    const formInspector = new FormInspector(formStore);

    if (!formInspector.locale) {
        throw new Error('Locale must have a value in formInspector');
    }

    expect(formInspector.locale.get()).toEqual('de');
});

test('Should return the id from the FormStore', () => {
    const formStore = new FormStore(new ResourceStore('test', 3));
    const formInspector = new FormInspector(formStore);

    expect(formInspector.id).toEqual(3);
});

test('Should return the value for a path by using the FormStore', () => {
    const data = [];
    const formStore = new FormStore(new ResourceStore('test', 3));
    formStore.getValueByPath.mockReturnValue(data);
    const formInspector = new FormInspector(formStore);

    expect(formInspector.getValueByPath('/test')).toBe(data);
    expect(formStore.getValueByPath).toBeCalledWith('/test');
});

test('Should return the values for a given tag by using the FormStore', () => {
    const data = [];
    const formStore = new FormStore(new ResourceStore('test', 3));
    formStore.getValuesByTag.mockReturnValue(data);
    const formInspector = new FormInspector(formStore);

    expect(formInspector.getValuesByTag('/test')).toBe(data);
    expect(formStore.getValuesByTag).toBeCalledWith('/test');
});
