// @flow
import React from 'react';
import {shallow} from 'enzyme';
import fieldTypeDefaultProps from 'sulu-admin-bundle/utils/TestHelper/fieldTypeDefaultProps';
import {FormInspector, FormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import RoleAssignments from '../../fields/RoleAssignments';

jest.mock('sulu-admin-bundle/containers', () => ({
    FormInspector: jest.fn(function(formStore) {
        this.getValueByPath = jest.fn();
        this.locale = formStore.locale;
    }),
    FormStore: jest.fn(function(resourceStore) {
        this.locale = resourceStore.locale;
    }),
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function(resourceKey, id, observableOptions = {}) {
        this.locale = observableOptions.locale;
    }),
}));

test('Pass props correctly to RoleAssignments', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));

    const roleAssignemnts = shallow(
        <RoleAssignments
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
        />
    );

    expect(roleAssignemnts.prop('value')).toEqual([]);
});

test('Pass props with value correctly to RoleAssignments', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));

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

    const roleAssignments = shallow(
        <RoleAssignments
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(roleAssignments.prop('value')).toEqual(value);
});
