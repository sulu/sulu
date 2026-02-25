// @flow
import React from 'react';
import {render} from '@testing-library/react';
import fieldTypeDefaultProps from 'sulu-admin-bundle/utils/TestHelper/fieldTypeDefaultProps';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import RoleAssignments from '../../fields/RoleAssignments';
import RoleAssignmentsContainer from '../../../RoleAssignments';

jest.mock('sulu-admin-bundle/containers', () => ({
    FormInspector: jest.fn(function(formStore) {
        this.getValueByPath = jest.fn();
        this.locale = formStore.locale;
    }),
    ResourceFormStore: jest.fn(function(resourceStore) {
        this.locale = resourceStore.locale;
    }),
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function(resourceKey, id, observableOptions = {}) {
        this.locale = observableOptions.locale;
    }),
}));

jest.mock('../../../RoleAssignments', () => jest.fn(() => null));

test('Pass props correctly to RoleAssignments', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    render(
        <RoleAssignments
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
        />
    );

    const roleAssignmentsProps: any = getLatestMockProps((RoleAssignmentsContainer: any));
    expect(roleAssignmentsProps.value).toEqual([]);
});

test('Pass props with value correctly to RoleAssignments', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

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
            formInspector={formInspector}
            value={value}
        />
    );

    const roleAssignmentsProps: any = getLatestMockProps((RoleAssignmentsContainer: any));
    expect(roleAssignmentsProps.disabled).toEqual(true);
    expect(roleAssignmentsProps.value).toEqual(value);
});
