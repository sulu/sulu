// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import {webspaceStore} from 'sulu-page-bundle/stores';
import RolePermissions from '../../fields/RolePermissions';
import RolePermissionsContainer from '../../../RolePermissions';

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

jest.mock('../../../RolePermissions', () => {
    const MockRolePermissionsContainer: any = jest.fn(() => null);
    MockRolePermissionsContainer.defaultProps = {
        disabled: false,
    };

    return MockRolePermissionsContainer;
});

test('Pass props correctly to component', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'), 'snippets', {resourceKey: 'snippets'}
        )
    );

    const value = {};

    render(
        <RolePermissions
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(webspaceStore.getWebspace).not.toBeCalled();

    const rolePermissionsProps: any = getLatestMockProps((RolePermissionsContainer: any));
    expect(rolePermissionsProps.disabled).toEqual(false);
    expect(rolePermissionsProps.permissionCheck).toBe(undefined);
    expect(rolePermissionsProps.resourceKey).toEqual('snippets');
    expect(rolePermissionsProps.system).toBe(undefined);
    expect(rolePermissionsProps.value).toBe(value);
});

test('Pass disabled prop correctly to component', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'), 'snippets', {resourceKey: 'snippets'}
        )
    );

    render(
        <RolePermissions
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
        />
    );

    const rolePermissionsProps: any = getLatestMockProps((RolePermissionsContainer: any));
    expect(rolePermissionsProps.disabled).toEqual(true);
    expect(rolePermissionsProps.value).toEqual({});
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
                    permissionCheck: true,
                },
            };
        }
    });

    webspaceStore.hasWebspace.mockImplementation((webspaceKey) => {
        if (webspaceKey === 'test') {
            return true;
        }
    });

    render(
        <RolePermissions
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
        />
    );

    const rolePermissionsProps: any = getLatestMockProps((RolePermissionsContainer: any));
    expect(rolePermissionsProps.permissionCheck).toEqual(true);
    expect(rolePermissionsProps.system).toEqual('test_security');
    expect(rolePermissionsProps.webspaceKey).toEqual('test');
});

test('Call onChange and onFinish correctly', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'), 'snippets', {resourceKey: 'snippets'}
        )
    );

    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    render(
        <RolePermissions
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    const rolePermissionsProps: any = getLatestMockProps((RolePermissionsContainer: any));
    rolePermissionsProps.onChange({});

    expect(changeSpy).toBeCalledWith({});
    expect(finishSpy).toBeCalledWith();
});
