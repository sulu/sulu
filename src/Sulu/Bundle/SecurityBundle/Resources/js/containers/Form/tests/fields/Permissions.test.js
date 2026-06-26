// @flow
import React from 'react';
import {render} from '@testing-library/react';
import fieldTypeDefaultProps from 'sulu-admin-bundle/utils/TestHelper/fieldTypeDefaultProps';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import PermissionsContainer from '../../../Permissions';
import Permissions from '../../fields/Permissions';
import type {ContextPermission} from '../../../Permissions';

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

jest.mock('../../../Permissions', () => jest.fn(() => null));

const PermissionsContainerMock = (PermissionsContainer: any);

test('Pass props correctly to Permissions', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    formInspector.getValueByPath.mockImplementation((path) => {
        switch (path) {
            case '/system':
                return 'Sulu';
        }
    });

    render(
        <Permissions
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
        />
    );

    expect(PermissionsContainerMock.mock.calls[0][0].system).toEqual('Sulu');
    expect(PermissionsContainerMock.mock.calls[0][0].value).toEqual([]);
    expect(PermissionsContainerMock.mock.calls[0][0].disabled).toEqual(true);
});

test('Pass props with value correctly to Permissions', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
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

    render(
        <Permissions
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(PermissionsContainerMock.mock.calls[0][0].system).toEqual('Sulu');
    expect(PermissionsContainerMock.mock.calls[0][0].value).toEqual(value);
});
