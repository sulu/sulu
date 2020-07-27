// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import {webspaceStore} from 'sulu-page-bundle/stores';
import RolePermissions from '../../fields/RolePermissions';

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn());

jest.mock(
    'sulu-admin-bundle/containers/Form/stores/ResourceFormStore',
    () => jest.fn(function(resourceStore, formKey, options) {
        this.options = options;
    })
);

jest.mock('sulu-admin-bundle/containers/Form/FormInspector', () => jest.fn(function(formStore) {
    this.options = formStore.options;
}));

jest.mock('sulu-page-bundle/stores/webspaceStore/webspaceStore', () => ({
    getWebspace: jest.fn(),
    hasWebspace: jest.fn(),
}));

test('Pass props correctly to component', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'), 'snippets', {resourceKey: 'snippets'}
        )
    );

    const value = {};

    const rolePermissions = shallow(
        <RolePermissions
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(webspaceStore.getWebspace).not.toBeCalled();

    expect(rolePermissions.find('RolePermissions').prop('disabled')).toEqual(false);
    expect(rolePermissions.find('RolePermissions').prop('resourceKey')).toEqual('snippets');
    expect(rolePermissions.find('RolePermissions').prop('system')).toBe(undefined);
    expect(rolePermissions.find('RolePermissions').prop('value')).toBe(value);
});

test('Pass disabled prop correctly to component', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'), 'snippets', {resourceKey: 'snippets'}
        )
    );

    const rolePermissions = shallow(
        <RolePermissions
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
        />
    );

    expect(rolePermissions.find('RolePermissions').prop('disabled')).toEqual(true);
    expect(rolePermissions.find('RolePermissions').prop('value')).toEqual({});
});

test('Pass system prop correctly to component', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'), 'snippets', {resourceKey: 'snippets', webspace: 'test'}
        )
    );

    webspaceStore.getWebspace.mockImplementation((webspaceKey) => {
        if (webspaceKey === 'test') {
            return {
                key: 'test',
                security: {
                    system: 'test_security',
                },
            };
        }
    });

    webspaceStore.hasWebspace.mockImplementation((webspaceKey) => {
        if (webspaceKey === 'test') {
            return true;
        }
    });

    const rolePermissions = shallow(
        <RolePermissions
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
        />
    );

    expect(rolePermissions.find('RolePermissions').prop('system')).toEqual('test_security');
    expect(rolePermissions.find('RolePermissions').prop('webspaceKey')).toEqual('test');
});

test('Pass disabled prop correctly to component', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'), 'snippets', {resourceKey: 'snippets'}
        )
    );

    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const rolePermissions = shallow(
        <RolePermissions
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    rolePermissions.find('RolePermissions').prop('onChange')({});

    expect(changeSpy).toBeCalledWith({});
    expect(finishSpy).toBeCalledWith();
});
