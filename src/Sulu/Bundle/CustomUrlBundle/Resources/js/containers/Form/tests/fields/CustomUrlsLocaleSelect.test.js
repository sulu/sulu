// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import {webspaceStore} from 'sulu-page-bundle/stores';
import CustomUrlsLocaleSelect from '../../fields/CustomUrlsLocaleSelect';

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

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-page-bundle/stores', () => ({
    webspaceStore: {
        loadWebspace: jest.fn(),
    },
}));

test('Pass correct props to MultiSelect', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'),
            'test',
            {webspace: 'sulu_io'}
        )
    );

    const webspacePromise = Promise.resolve({
        allLocalizations: [
            {localization: 'de'},
            {localization: 'en'},
        ],
    });
    webspaceStore.loadWebspace.mockReturnValue(webspacePromise);

    const customUrlsDomainSelect = shallow(
        <CustomUrlsLocaleSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value="en"
        />
    );

    expect(webspaceStore.loadWebspace).toBeCalledWith('sulu_io');

    return webspacePromise.then(() => {
        expect(customUrlsDomainSelect.find('SingleSelect').prop('disabled')).toEqual(true);
        expect(customUrlsDomainSelect.find('SingleSelect').prop('value')).toEqual('en');
        expect(customUrlsDomainSelect.find('Option').at(0).prop('children')).toEqual('de');
        expect(customUrlsDomainSelect.find('Option').at(0).prop('value')).toEqual('de');
        expect(customUrlsDomainSelect.find('Option').at(1).prop('children')).toEqual('en');
        expect(customUrlsDomainSelect.find('Option').at(1).prop('value')).toEqual('en');
    });
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

    const webspacePromise = Promise.resolve({
        allLocalizations: [
            {localization: 'de'},
            {localization: 'en'},
        ],
    });
    webspaceStore.loadWebspace.mockReturnValue(webspacePromise);

    const customUrlsDomainSelect = shallow(
        <CustomUrlsLocaleSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value="de"
        />
    );

    return webspacePromise.then(() => {
        customUrlsDomainSelect.find('SingleSelect').prop('onChange')('en');
        expect(changeSpy).toBeCalledWith('en');
        expect(finishSpy).toBeCalledWith();
    });
});
