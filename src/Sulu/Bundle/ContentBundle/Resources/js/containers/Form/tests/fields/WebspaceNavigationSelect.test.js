// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {FormInspector, FormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import webspaceStore from '../../../../stores/WebspaceStore';
import WebspaceNavigationSelect from '../../fields/WebspaceNavigationSelect';

jest.mock('sulu-admin-bundle/containers', () => ({
    FormInspector: jest.fn(function(formStore) {
        this.options = formStore.options;
    }),
    FormStore: jest.fn(function(resourceStore, options) {
        this.options = options;
    }),
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(),
}));

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../stores/WebspaceStore', () => ({
    loadWebspace: jest.fn(),
}));

test('Pass correct props to MultiSelect', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test'), {webspace: 'sulu_io'}));

    const webspacePromise = Promise.resolve({
        navigations: [
            {key: 'main', title: 'Main Navigation'},
            {key: 'footer', title: 'Footer Navigation'},
        ],
    });
    webspaceStore.loadWebspace.mockReturnValue(webspacePromise);

    const webspaceNavigationSelect = shallow(
        <WebspaceNavigationSelect
            dataPath="/test"
            error={undefined}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            label="Test"
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath="/test"
            showAllErrors={false}
            types={undefined}
            value={['footer']}
        />
    );

    expect(webspaceStore.loadWebspace).toBeCalledWith('sulu_io');

    return webspacePromise.then(() => {
        expect(webspaceNavigationSelect.find('MultiSelect').prop('values')).toEqual(['footer']);
        expect(webspaceNavigationSelect.find('Option').at(0).prop('children')).toEqual('Main Navigation');
        expect(webspaceNavigationSelect.find('Option').at(0).prop('value')).toEqual('main');
        expect(webspaceNavigationSelect.find('Option').at(1).prop('children')).toEqual('Footer Navigation');
        expect(webspaceNavigationSelect.find('Option').at(1).prop('value')).toEqual('footer');
    });
});

test('Call onChange an onBlur if the value is changed', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const formInspector = new FormInspector(new FormStore(new ResourceStore('test'), {webspace: 'sulu_io'}));

    const webspacePromise = Promise.resolve({
        navigations: [
            {key: 'main', title: 'Main Navigation'},
            {key: 'footer', title: 'Footer Navigation'},
        ],
    });
    webspaceStore.loadWebspace.mockReturnValue(webspacePromise);

    const webspaceNavigationSelect = shallow(
        <WebspaceNavigationSelect
            dataPath="/test"
            error={undefined}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            label="Test"
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaPath="/test"
            showAllErrors={false}
            types={undefined}
            value={['footer']}
        />
    );

    return webspacePromise.then(() => {
        webspaceNavigationSelect.find('MultiSelect').prop('onChange')(['footer', 'main']);
        expect(changeSpy).toBeCalledWith(['footer', 'main']);
        expect(finishSpy).toBeCalledWith();
    });
});
