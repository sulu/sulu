// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import {webspaceStore} from 'sulu-page-bundle/stores';
import CustomUrlsDomainSelect from '../../fields/CustomUrlsDomainSelect';

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
        customUrls: [
            {url: 'www.sulu.io/*'},
            {url: '*.sulu.io'},
        ],
    });
    webspaceStore.loadWebspace.mockReturnValue(webspacePromise);

    const customUrlsDomainSelect = shallow(
        <CustomUrlsDomainSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value="www.sulu.io/*"
        />
    );

    expect(webspaceStore.loadWebspace).toBeCalledWith('sulu_io');

    return webspacePromise.then(() => {
        expect(customUrlsDomainSelect.find('SingleSelect').prop('disabled')).toEqual(true);
        expect(customUrlsDomainSelect.find('SingleSelect').prop('value')).toEqual('www.sulu.io/*');
        expect(customUrlsDomainSelect.find('Option').at(0).prop('children')).toEqual('www.sulu.io/*');
        expect(customUrlsDomainSelect.find('Option').at(0).prop('value')).toEqual('www.sulu.io/*');
        expect(customUrlsDomainSelect.find('Option').at(1).prop('children')).toEqual('*.sulu.io');
        expect(customUrlsDomainSelect.find('Option').at(1).prop('value')).toEqual('*.sulu.io');
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
        customUrls: [
            {url: 'www.sulu.io/*'},
            {url: '*.sulu.io'},
        ],
    });
    webspaceStore.loadWebspace.mockReturnValue(webspacePromise);

    const customUrlsDomainSelect = shallow(
        <CustomUrlsDomainSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value="www.sulu.io/*"
        />
    );

    return webspacePromise.then(() => {
        customUrlsDomainSelect.find('SingleSelect').prop('onChange')('*.sulu.io');
        expect(changeSpy).toBeCalledWith('*.sulu.io');
        expect(finishSpy).toBeCalledWith();
    });
});
