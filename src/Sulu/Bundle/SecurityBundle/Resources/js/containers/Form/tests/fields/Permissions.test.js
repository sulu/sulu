// @flow
import React from 'react';
import {shallow} from 'enzyme';
import fieldTypeDefaultProps from 'sulu-admin-bundle/utils/TestHelper/fieldTypeDefaultProps';
import {FormInspector, FormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import Permissions from '../../fields/Permissions';
import type {ContextPermission} from '../../../Permissions';

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

test('Pass props correctly to Permissions', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    formInspector.getValueByPath.mockImplementation((path) => {
        switch (path) {
            case '/system':
                return 'Sulu';
        }
    });

    const permissions = shallow(
        <Permissions
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
        />
    );

    expect(permissions.prop('system')).toEqual('Sulu');
    expect(permissions.prop('value')).toEqual([]);
});

test('Pass props with value correctly to Permissions', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    formInspector.getValueByPath.mockImplementation((path) => {
        switch (path) {
            case '/system':
                return 'Sulu';
        }
    });

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

    const permissions = shallow(
        <Permissions
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(permissions.prop('system')).toEqual('Sulu');
    expect(permissions.prop('value')).toEqual(value);
});
