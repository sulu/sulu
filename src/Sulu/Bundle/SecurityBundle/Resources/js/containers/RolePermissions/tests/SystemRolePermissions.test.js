// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {findElementByType, renderWithRef} from 'sulu-admin-bundle/utils/TestHelper';
import securityContextStore from '../../../stores/securityContextStore';
import SystemRolePermissions from '../SystemRolePermissions';

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('../../../stores/securityContextStore', () => ({
    getAvailableActions: jest.fn(),
    getSecurityContextByResourceKey: jest.fn(),
}));

test('Render permissions for a single system', () => {
    const roles = [
        {id: 2, identifier: '', name: 'User', permissions: [], system: 'Sulu'},
        {id: 3, identifier: '', name: 'Contact Manager', permissions: [], system: 'Sulu'},
    ];

    const {container} = render(
        <SystemRolePermissions
            actions={['view', 'add', 'edit']}
            disabled={false}
            onChange={jest.fn()}
            resourceKey="test"
            roles={roles}
            system="Sulu"
            values={{'2': {view: true, add: false, edit: true}, '3': {view: false, add: true, edit: false}}}
        />
    );

    expect(container).toMatchSnapshot();
});

test('Do not show Matrix if no values are given', () => {
    const roles = [
        {id: 2, identifier: '', name: 'User', permissions: [], system: 'Sulu'},
        {id: 3, identifier: '', name: 'Contact Manager', permissions: [], system: 'Sulu'},
    ];

    const {instance: systemRolePermissions} = renderWithRef(
        <SystemRolePermissions
            actions={['view', 'add', 'edit']}
            disabled={false}
            onChange={jest.fn()}
            resourceKey="test"
            roles={roles}
            system="Sulu"
            values={{}}
        />
    );

    expect(() => findElementByType(systemRolePermissions.render(), 'Matrix')).toThrow('Element not found');
    expect(findElementByType(systemRolePermissions.render(), 'Toggler').props.checked).toEqual(false);
});

test('Render permissions for a single system in disabled state', () => {
    const {instance: systemRolePermissions} = renderWithRef(
        <SystemRolePermissions
            actions={[]}
            disabled={true}
            onChange={jest.fn()}
            resourceKey="test"
            roles={[]}
            system="Sulu"
            values={{'2': {view: true, add: false, edit: true}}}
        />
    );

    expect(findElementByType(systemRolePermissions.render(), 'Matrix').props.disabled).toEqual(true);
});

test('Call onChange callback when matrix changes', () => {
    const changeSpy = jest.fn();

    const {instance: systemRolePermissions} = renderWithRef(
        <SystemRolePermissions
            actions={['view']}
            disabled={false}
            onChange={changeSpy}
            resourceKey="test"
            roles={[]}
            system="Sulu"
            values={{}}
        />
    );

    findElementByType(systemRolePermissions.render(), 'Toggler').props.onChange(true);

    const newValue = {'1': {view: true}};
    findElementByType(systemRolePermissions.render(), 'Matrix').props.onChange(newValue);

    expect(changeSpy).toHaveBeenCalledWith(newValue, 'Sulu');
});

test('Call onChange callback with empty values if toggler is deactivated', () => {
    const changeSpy = jest.fn();

    const {instance: systemRolePermissions} = renderWithRef(
        <SystemRolePermissions
            actions={['view']}
            disabled={false}
            onChange={changeSpy}
            resourceKey="test"
            roles={[]}
            system="Sulu"
            values={{'1': {view: true}}}
        />
    );

    findElementByType(systemRolePermissions.render(), 'Toggler').props.onChange(false);

    expect(changeSpy).toHaveBeenCalledWith({}, 'Sulu');
});

test('Show default values after activating toggler', () => {
    const changeSpy = jest.fn();

    const roles = [
        {
            id: 2,
            identifier: '',
            name: 'User',
            permissions: [
                {context: 'sulu.test', permissions: {view: true, add: true, edit: true}},
            ],
            system: 'Sulu',
        },
        {
            id: 3,
            identifier: '',
            name: 'Contact Manager',
            permissions: [
                {context: 'sulu.test', permissions: {view: true, add: false, edit: true}},
            ],
            system: 'Sulu',
        },
    ];

    securityContextStore.getSecurityContextByResourceKey.mockImplementation((resourceKey) => {
        switch (resourceKey) {
            case 'test':
                return 'sulu.test';
        }
    });
    securityContextStore.getAvailableActions.mockReturnValue(['view', 'add', 'edit']);

    const {instance: systemRolePermissions} = renderWithRef(
        <SystemRolePermissions
            actions={['view']}
            disabled={false}
            onChange={changeSpy}
            resourceKey="test"
            roles={roles}
            system="Sulu"
            values={{}}
        />
    );

    expect(() => findElementByType(systemRolePermissions.render(), 'Matrix')).toThrow('Element not found');
    findElementByType(systemRolePermissions.render(), 'Toggler').props.onChange(true);
    expect(findElementByType(systemRolePermissions.render(), 'Matrix')).toBeTruthy();

    expect(findElementByType(systemRolePermissions.render(), 'Matrix').props.values).toEqual({
        '2': {view: true, add: true, edit: true},
        '3': {view: true, add: false, edit: true},
    });
});
