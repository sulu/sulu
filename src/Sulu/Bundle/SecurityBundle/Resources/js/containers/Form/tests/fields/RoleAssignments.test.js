// @flow
import {render} from '@testing-library/react';
import React from 'react';
import fieldTypeDefaultProps from 'sulu-admin-bundle/utils/TestHelper/fieldTypeDefaultProps';
import RoleAssignments from '../../fields/RoleAssignments';
import RoleAssignmentsContainer from '../../../RoleAssignments';

jest.mock('../../../RoleAssignments', () => jest.fn(() => null));

beforeEach(() => {
    jest.clearAllMocks();
});

function createFormInspector() {
    return ({}: any);
}

test('Pass props correctly to RoleAssignments', () => {
    render(
        <RoleAssignments
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector()}
        />
    );

    const [roleAssignmentsProps] = (RoleAssignmentsContainer: any).mock.calls[0];
    expect(roleAssignmentsProps.value).toEqual([]);
});

test('Pass props with value correctly to RoleAssignments', () => {
    const value: Array<Object> = [
        {
            id: 1,
            role: {
                id: 99,
                name: 'Test 1',
                system: 'Sulu 1',
            },
            locales: ['de', 'en'],
        },
        {
            id: 2,
            role: {
                id: 232,
                name: 'Test 2',
                system: 'Sulu 2',
            },
            locales: ['de'],
        },
    ];

    render(
        <RoleAssignments
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={createFormInspector()}
            value={value}
        />
    );

    const [roleAssignmentsProps] = (RoleAssignmentsContainer: any).mock.calls[0];
    expect(roleAssignmentsProps.disabled).toEqual(true);
    expect(roleAssignmentsProps.value).toEqual(value);
});
