// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {observable} from 'mobx';
import {FormInspector, ListStore, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceRequester, Router} from 'sulu-admin-bundle/services';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import PageSettingsVersions from '../../fields/PageSettingsVersions';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    post: jest.fn(),
}));

jest.mock('sulu-admin-bundle/services/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
}));

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () => jest.fn(function() {
    this.reload = jest.fn();
}));

jest.mock('sulu-admin-bundle/containers/Form/FormInspector', () => jest.fn(function(resourceFormStore) {
    this.options = resourceFormStore.options;
    this.locale = resourceFormStore.locale;
    this.id = resourceFormStore.id;
    this.addSaveHandler = jest.fn();
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn(function(resourceStore) {
    this.options = resourceStore.options;
    this.locale = resourceStore.locale;
    this.id = resourceStore.id;
}));

jest.mock(
    'sulu-admin-bundle/stores/ResourceStore',
    () => jest.fn(function(resourceKey, id, observableOptions, options) {
        this.options = options;
        this.locale = observableOptions.locale;
        this.id = id;
    })
);

test('Initialize the list correctly', () => {
    const locale = observable.box('en');
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 3, {locale}, {webspace: 'sulu'}),
            'test'
        )
    );

    const pageSettingsVersions = shallow(
        <PageSettingsVersions {...fieldTypeDefaultProps} formInspector={formInspector} />
    );

    expect(ListStore).toBeCalledWith(
        'page_versions',
        'page_versions',
        'page_versions',
        expect.objectContaining({locale}),
        {id: 3, webspace: 'sulu'}
    );

    expect(pageSettingsVersions.find('List').props()).toEqual(expect.objectContaining({
        adapters: ['table'],
        searchable: false,
        selectable: false,
        // $FlowFixMe
        store: ListStore.mock.instances[0],
    }));
});

test('Reload the ListStore if a new version was published', () => {
    const locale = observable.box('en');
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 3, {locale}, {webspace: 'sulu'}),
            'test'
        )
    );

    shallow(
        <PageSettingsVersions {...fieldTypeDefaultProps} formInspector={formInspector} />
    );

    // $FlowFixMe
    const listStore = ListStore.mock.instances[0];
    const saveHandler = formInspector.addSaveHandler.mock.calls[0][0];
    saveHandler('publish');

    expect(listStore.reload).toBeCalledWith();
});

test('Do not reload the ListStore if page was saved without being published', () => {
    const locale = observable.box('en');
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 3, {locale}, {webspace: 'sulu'}),
            'test'
        )
    );

    shallow(
        <PageSettingsVersions {...fieldTypeDefaultProps} formInspector={formInspector} />
    );

    // $FlowFixMe
    const listStore = ListStore.mock.instances[0];
    const saveHandler = formInspector.addSaveHandler.mock.calls[0][0];
    saveHandler('draft');

    expect(listStore.reload).not.toBeCalled();
});

test('Open and cancel restore overlay', () => {
    const locale = observable.box('en');
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 3, {locale}, {webspace: 'sulu'}),
            'test'
        )
    );

    const pageSettingsVersions = shallow(
        <PageSettingsVersions {...fieldTypeDefaultProps} formInspector={formInspector} />
    );

    expect(pageSettingsVersions.find('Dialog').prop('open')).toEqual(false);
    pageSettingsVersions.find('List').prop('actions')[0].onClick(3);
    pageSettingsVersions.update();
    expect(pageSettingsVersions.find('Dialog').prop('open')).toEqual(true);
    pageSettingsVersions.find('Dialog').prop('onCancel')();
    expect(pageSettingsVersions.find('Dialog').prop('open')).toEqual(false);
});

test('Open and confirm restore overlay', () => {
    const locale = observable.box('en');
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 3, {locale}, {webspace: 'sulu'}),
            'test'
        )
    );

    const router = new Router();

    const postPromise = Promise.resolve();
    ResourceRequester.post.mockReturnValue(postPromise);

    const pageSettingsVersions = shallow(
        <PageSettingsVersions {...fieldTypeDefaultProps} formInspector={formInspector} router={router} />
    );

    expect(pageSettingsVersions.find('Dialog').prop('open')).toEqual(false);
    pageSettingsVersions.find('List').prop('actions')[0].onClick(3);
    pageSettingsVersions.update();
    expect(pageSettingsVersions.find('Dialog').prop('open')).toEqual(true);
    pageSettingsVersions.find('Dialog').prop('onConfirm')();

    expect(pageSettingsVersions.find('Dialog').prop('confirmLoading')).toEqual(true);
    return postPromise.then(() => {
        expect(pageSettingsVersions.find('Dialog').prop('open')).toEqual(false);
        expect(pageSettingsVersions.find('Dialog').prop('confirmLoading')).toEqual(false);
        expect(router.navigate).toBeCalledWith('sulu_page.page_edit_form', {id: 3, locale, webspace: 'sulu'});
    });
});
