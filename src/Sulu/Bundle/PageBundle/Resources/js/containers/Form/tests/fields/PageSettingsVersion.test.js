// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {observable} from 'mobx';
import {FormInspector, ListStore, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import PageSettingsVersions from '../../fields/PageSettingsVersions';

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () => jest.fn());

jest.mock('sulu-admin-bundle/containers/Form/FormInspector', () => jest.fn(function(resourceFormStore) {
    this.options = resourceFormStore.options;
    this.locale = resourceFormStore.locale;
    this.id = resourceFormStore.id;
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
