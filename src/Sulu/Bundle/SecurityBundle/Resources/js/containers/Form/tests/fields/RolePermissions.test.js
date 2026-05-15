// @flow
import {render} from '@testing-library/react';
import React from 'react';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import {webspaceStore} from 'sulu-page-bundle/stores';
import RolePermissions from '../../fields/RolePermissions';
import RolePermissionsContainer from '../../../RolePermissions';

jest.mock('../../../RolePermissions', () => jest.fn(() => null));

jest.mock('sulu-page-bundle/stores/webspaceStore/webspaceStore', () => ({
    getWebspace: jest.fn(),
    hasWebspace: jest.fn(),
}));

beforeEach(() => {
    jest.clearAllMocks();
});

function createFormInspector(options: Object = {resourceKey: 'snippets'}) {
    return ({
        options,
    }: any);
}

test('Pass props correctly to component', () => {
    const value = {};

    render(
        <RolePermissions
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector({resourceKey: 'snippets'})}
            value={value}
        />
    );

    expect(webspaceStore.getWebspace).not.toBeCalled();
    const [rolePermissionsProps] = (RolePermissionsContainer: any).mock.calls[0];
    expect(rolePermissionsProps.disabled).toEqual(undefined);
    expect(rolePermissionsProps.permissionCheck).toBe(undefined);
    expect(rolePermissionsProps.resourceKey).toEqual('snippets');
    expect(rolePermissionsProps.system).toBe(undefined);
    expect(rolePermissionsProps.value).toBe(value);
});

test('Pass disabled prop correctly to component', () => {
    render(
        <RolePermissions
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={createFormInspector({resourceKey: 'snippets'})}
        />
    );

    const [rolePermissionsProps] = (RolePermissionsContainer: any).mock.calls[0];
    expect(rolePermissionsProps.disabled).toEqual(true);
    expect(rolePermissionsProps.value).toEqual({});
});

test('Pass system prop correctly to component', () => {
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

        return undefined;
    });

    webspaceStore.hasWebspace.mockImplementation((webspaceKey) => webspaceKey === 'test');

    render(
        <RolePermissions
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={createFormInspector({resourceKey: 'snippets', webspace: 'test'})}
        />
    );

    const [rolePermissionsProps] = (RolePermissionsContainer: any).mock.calls[0];
    expect(rolePermissionsProps.permissionCheck).toEqual(true);
    expect(rolePermissionsProps.system).toEqual('test_security');
    expect(rolePermissionsProps.webspaceKey).toEqual('test');
});

test('Call onChange and onFinish callbacks', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    render(
        <RolePermissions
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={createFormInspector({resourceKey: 'snippets'})}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    const [rolePermissionsProps] = (RolePermissionsContainer: any).mock.calls[0];
    rolePermissionsProps.onChange({});

    expect(changeSpy).toBeCalledWith({});
    expect(finishSpy).toBeCalledWith();
});
