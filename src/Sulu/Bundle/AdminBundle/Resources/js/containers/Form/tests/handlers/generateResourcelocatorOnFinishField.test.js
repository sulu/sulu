// @flow
import {observable} from 'mobx';
import generateResourcelocatorOnFinishField from '../../handlers/generateResourcelocatorOnFinishField';
import Requester from '../../../../services/Requester';
import ResourceStore from '../../../../stores/ResourceStore';
import FormStore from '../../stores/FormStore';

jest.mock('../../../../services/Requester', () => ({
    post: jest.fn(),
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions) {
    this.id = id;
    this.locale = observableOptions ? observableOptions.locale.get() : undefined;
}));

jest.mock('../../stores/FormStore', () => jest.fn(function(resourceStore, options) {
    this.id = resourceStore.id;
    this.options = options;
    this.locale = resourceStore.locale;
    this.getValuesByTag = jest.fn();
    this.setValueByTag = jest.fn();
}));

test('Should not request a new URL if on an edit form', () =>{
    const formStore = new FormStore(new ResourceStore('pages', 4));
    generateResourcelocatorOnFinishField(formStore);
    expect(Requester.post).not.toBeCalled();
});

test('Should request a new URL if on an add form', () => {
    const formStore = new FormStore(new ResourceStore('pages', undefined, {locale: observable.box('en')}));
    formStore.getValuesByTag.mockReturnValue(['te', 'st']);

    const resourceLocatorPromise = Promise.resolve({
        resourcelocator: '/test',
    });
    Requester.post.mockReturnValue(resourceLocatorPromise);

    generateResourcelocatorOnFinishField(formStore);

    expect(formStore.getValuesByTag).toBeCalledWith('sulu.rlp.part');
    expect(Requester.post).toBeCalledWith(
        '/admin/api/resourcelocators?action=generate',
        {locale: 'en', parts: ['te', 'st']}
    );

    return resourceLocatorPromise.then(() => {
        expect(formStore.setValueByTag).toBeCalledWith('sulu.rlp', '/test');
    });
});

test('Should request a new URL including the options from the FormStore if on an add form', () => {
    const formStore = new FormStore(
        new ResourceStore('pages', undefined),
        {webspace: 'example'}
    );
    formStore.getValuesByTag.mockReturnValue(['te', 'st']);

    const resourceLocatorPromise = Promise.resolve({
        resourcelocator: '/test',
    });
    Requester.post.mockReturnValue(resourceLocatorPromise);

    generateResourcelocatorOnFinishField(formStore);

    expect(formStore.getValuesByTag).toBeCalledWith('sulu.rlp.part');
    expect(Requester.post).toBeCalledWith(
        '/admin/api/resourcelocators?action=generate',
        {locale: undefined, parts: ['te', 'st'], webspace: 'example'}
    );

    return resourceLocatorPromise.then(() => {
        expect(formStore.setValueByTag).toBeCalledWith('sulu.rlp', '/test');
    });
});

test('Should not request a new URL if no parts are available', () => {
    const formStore = new FormStore(new ResourceStore('pages', undefined, {locale: observable.box('en')}));
    formStore.getValuesByTag.mockReturnValue([]);

    const resourceLocatorPromise = Promise.resolve({
        resourcelocator: '/test',
    });
    Requester.post.mockReturnValue(resourceLocatorPromise);

    generateResourcelocatorOnFinishField(formStore);

    expect(formStore.getValuesByTag).toBeCalledWith('sulu.rlp.part');
    expect(Requester.post).not.toBeCalled();
});

test('Should not request a new URL if only empty parts are available', () => {
    const formStore = new FormStore(new ResourceStore('pages', undefined, {locale: observable.box('en')}));
    formStore.getValuesByTag.mockReturnValue([null, undefined]);

    const resourceLocatorPromise = Promise.resolve({
        resourcelocator: '/test',
    });
    Requester.post.mockReturnValue(resourceLocatorPromise);

    generateResourcelocatorOnFinishField(formStore);

    expect(formStore.getValuesByTag).toBeCalledWith('sulu.rlp.part');
    expect(Requester.post).not.toBeCalled();
});
