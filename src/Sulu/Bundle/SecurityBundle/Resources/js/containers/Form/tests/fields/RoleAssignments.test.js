// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import fieldTypeDefaultProps from 'sulu-admin-bundle/utils/TestHelper/fieldTypeDefaultProps';
import RoleAssignments from '../../fields/RoleAssignments';

jest.mock('sulu-admin-bundle/containers', () => ({
    ResourceMultiSelect: jest.fn(() => null),
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    localizationStore: {
        localizations: [],
    },
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Pass props correctly to RoleAssignments', () => {
    render(
        <RoleAssignments
            {...fieldTypeDefaultProps}
            formInspector={({}: any)}
        />
    );

    expect(screen.queryByRole('table')).not.toBeInTheDocument();
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
            formInspector={({}: any)}
            value={value}
        />
    );

    expect(screen.getByText('Test 1')).toBeInTheDocument();
    expect(screen.getByText('Sulu 1')).toBeInTheDocument();
    expect(screen.getByText('Test 2')).toBeInTheDocument();
    expect(screen.getByText('Sulu 2')).toBeInTheDocument();
});
