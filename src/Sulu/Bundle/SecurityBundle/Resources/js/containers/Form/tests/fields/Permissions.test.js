// @flow
import {render} from '@testing-library/react';
import React from 'react';
import fieldTypeDefaultProps from 'sulu-admin-bundle/utils/TestHelper/fieldTypeDefaultProps';
import Permissions from '../../fields/Permissions';
import PermissionsContainer from '../../../Permissions';
import type {ContextPermission} from '../../../Permissions';

jest.mock('../../../Permissions', () => jest.fn(() => null));

beforeEach(() => {
    jest.clearAllMocks();
});

function createFormInspector(system: ?string) {
    return ({
        getValueByPath: jest.fn((path) => (path === '/system' ? system : undefined)),
    }: any);
}

test('Pass props correctly to Permissions', () => {
    render(
        <Permissions
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={createFormInspector('Sulu')}
        />
    );

    const [permissionsProps] = (PermissionsContainer: any).mock.calls[0];
    expect(permissionsProps.system).toEqual('Sulu');
    expect(permissionsProps.value).toEqual([]);
    expect(permissionsProps.disabled).toEqual(true);
});

test('Pass props with value correctly to Permissions', () => {
    const value: Array<ContextPermission> = [
        {
            id: 1,
            context: 'sulu.contact.people',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
        {
            id: 2,
            context: 'sulu.contact.organizations',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
    ];

    render(
        <Permissions
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector('Sulu')}
            value={value}
        />
    );

    const [permissionsProps] = (PermissionsContainer: any).mock.calls[0];
    expect(permissionsProps.system).toEqual('Sulu');
    expect(permissionsProps.value).toEqual(value);
});
