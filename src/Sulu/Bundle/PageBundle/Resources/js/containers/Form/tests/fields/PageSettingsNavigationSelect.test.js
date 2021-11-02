// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import webspaceStore from '../../../../stores/webspaceStore';
import PageSettingsNavigationSelect from '../../fields/PageSettingsNavigationSelect';

jest.mock('sulu-admin-bundle/containers', () => ({
    FormInspector: jest.fn(function(formStore) {
        this.options = formStore.options;
    }),
    ResourceFormStore: jest.fn(function(resourceStore, formKey, options) {
        this.options = options;
    }),
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(),
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../stores/webspaceStore', () => ({
    getWebspace: jest.fn(),
}));

test('Pass correct props to MultiSelect', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'),
            'test',
            {webspace: 'sulu_io'}
        )
    );

    const webspace = {
        navigations: [
            {key: 'main', title: 'Main Navigation'},
            {key: 'footer', title: 'Footer Navigation'},
        ],
    };
    webspaceStore.getWebspace.mockReturnValue(webspace);

    const pageSettingsNavigationSelect = shallow(
        <PageSettingsNavigationSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value={['footer']}
        />
    );

    expect(webspaceStore.getWebspace).toBeCalledWith('sulu_io');

    expect(pageSettingsNavigationSelect.find('MultiSelect').prop('disabled')).toEqual(true);
    expect(pageSettingsNavigationSelect.find('MultiSelect').prop('values')).toEqual(['footer']);
    expect(pageSettingsNavigationSelect.find('Option').at(0).prop('children')).toEqual('Main Navigation');
    expect(pageSettingsNavigationSelect.find('Option').at(0).prop('value')).toEqual('main');
    expect(pageSettingsNavigationSelect.find('Option').at(1).prop('children')).toEqual('Footer Navigation');
    expect(pageSettingsNavigationSelect.find('Option').at(1).prop('value')).toEqual('footer');
});

test('Call onChange and onBlur if the value is changed', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'),
            'test',
            {webspace: 'sulu_io'}
        )
    );

    const webspace = {
        navigations: [
            {key: 'main', title: 'Main Navigation'},
            {key: 'footer', title: 'Footer Navigation'},
        ],
    };
    webspaceStore.getWebspace.mockReturnValue(webspace);

    const pageSettingsNavigationSelect = shallow(
        <PageSettingsNavigationSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={['footer']}
        />
    );

    pageSettingsNavigationSelect.find('MultiSelect').prop('onChange')(['footer', 'main']);
    expect(changeSpy).toBeCalledWith(['footer', 'main']);
    expect(finishSpy).toBeCalledWith();
});
