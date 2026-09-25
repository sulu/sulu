// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import {webspaceStore} from 'sulu-page-bundle/stores';
import RolePermissionsContainer from '../../../RolePermissions';
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

jest.mock('../../../RolePermissions', () => jest.fn(() => null));

const RolePermissionsContainerMock = (RolePermissionsContainer: any);

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

    expect(webspaceStore.getWebspace).not.toHaveBeenCalled();

    expect(RolePermissionsContainerMock.mock.calls[0][0].disabled).toEqual(undefined);
    expect(RolePermissionsContainerMock.mock.calls[0][0].permissionCheck).toBe(undefined);
    expect(RolePermissionsContainerMock.mock.calls[0][0].resourceKey).toEqual('snippets');
    expect(RolePermissionsContainerMock.mock.calls[0][0].system).toBe(undefined);
    expect(RolePermissionsContainerMock.mock.calls[0][0].value).toBe(value);
});

test('Pass onChange and onFinish props correctly to component', () => {
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

    expect(RolePermissionsContainerMock.mock.calls[0][0].disabled).toEqual(true);
    expect(RolePermissionsContainerMock.mock.calls[0][0].value).toEqual({});
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

    expect(RolePermissionsContainerMock.mock.calls[0][0].permissionCheck).toEqual(true);
    expect(RolePermissionsContainerMock.mock.calls[0][0].system).toEqual('test_security');
    expect(RolePermissionsContainerMock.mock.calls[0][0].webspaceKey).toEqual('test');
});

test('Pass disabled prop correctly to component', () => {
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

    RolePermissionsContainerMock.mock.calls[0][0].onChange({});

    expect(changeSpy).toHaveBeenCalledWith({});
    expect(finishSpy).toHaveBeenCalledWith();
});
