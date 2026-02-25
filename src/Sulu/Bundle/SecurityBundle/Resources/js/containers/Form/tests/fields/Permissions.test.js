// @flow
import React from 'react';
import {render} from '@testing-library/react';
import fieldTypeDefaultProps from 'sulu-admin-bundle/utils/TestHelper/fieldTypeDefaultProps';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import Permissions from '../../fields/Permissions';
import type {ContextPermission} from '../../../Permissions';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-page-bundle/stores', () => ({
    webspaceStore: {
        allWebspaces: [],
    },
}));

jest.mock('../../../../stores/securityContextStore', () => ({
    getSecurityContextGroups: jest.fn(() => ({
        Security: {
            'sulu.contact.people': ['view', 'add', 'edit', 'delete'],
            'sulu.contact.organizations': ['view', 'add', 'edit', 'delete'],
        },
    })),
}));

jest.mock('../../../Permissions/PermissionMatrix', () => jest.fn(() => null));

const permissionMatrixComponent = ((jest.requireMock('../../../Permissions/PermissionMatrix'): any): {
    mock: {calls: Array<[Object]>},
    ...
});

test('Pass props correctly to Permissions', () => {
    const formInspector = ({
        getValueByPath: jest.fn(() => 'Sulu'),
    }: any);

    render(
        <Permissions
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
        />
    );

    expect(getLatestMockProps(permissionMatrixComponent).title).toBe('Security');
    expect(getLatestMockProps(permissionMatrixComponent).disabled).toBe(true);
});

test('Pass props with value correctly to Permissions', () => {
    const formInspector = ({
        getValueByPath: jest.fn(() => 'Sulu'),
    }: any);

    const value: Array<ContextPermission> = [
        {
            id: 1,
            context: 'sulu.contact.people',
            permissions: {
                view: true,
                delete: true,
                add: true,
                edit: true,
            },
        },
        {
            id: 2,
            context: 'sulu.contact.organizations',
            permissions: {
                view: true,
                delete: true,
                add: true,
                edit: true,
            },
        },
    ];

    render(
        <Permissions
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(getLatestMockProps(permissionMatrixComponent).title).toBe('Security');
    expect(getLatestMockProps(permissionMatrixComponent).contextPermissions).toBe(value);
});
