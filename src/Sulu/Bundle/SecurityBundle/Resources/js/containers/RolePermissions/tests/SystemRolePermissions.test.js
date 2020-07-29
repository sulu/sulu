// @flow
import React from 'react';
import {mount, shallow} from 'enzyme';
import securityContextStore from '../../../stores/securityContextStore';
import SystemRolePermissions from '../SystemRolePermissions';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../stores/securityContextStore', () => ({
    getAvailableActions: jest.fn(),
    getSecurityContextByResourceKey: jest.fn(),
}));

test('Render permissions for a single system', () => {
    const roles = [
        {id: 2, identifier: '', name: 'User', permissions: [], system: 'Sulu'},
        {id: 3, identifier: '', name: 'Contact Manager', permissions: [], system: 'Sulu'},
    ];

    const systemRolePermissions = mount(
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

    expect(systemRolePermissions.render()).toMatchSnapshot();
});

test('Do not show Matrix if no values are given', () => {
    const roles = [
        {id: 2, identifier: '', name: 'User', permissions: [], system: 'Sulu'},
        {id: 3, identifier: '', name: 'Contact Manager', permissions: [], system: 'Sulu'},
    ];

    const systemRolePermissions = mount(
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

    expect(systemRolePermissions.find('Matrix')).toHaveLength(0);
    expect(systemRolePermissions.find('Toggler').prop('checked')).toEqual(false);
});

test('Render permissions for a single system in disabled state', () => {
    const systemRolePermissions = shallow(
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

    expect(systemRolePermissions.find('Matrix').prop('disabled')).toEqual(true);
});

test('Call onChange callback when matrix changes', () => {
    const changeSpy = jest.fn();

    const systemRolePermissions = shallow(
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

    systemRolePermissions.find('Toggler').simulate('change', true);

    const newValue = {'1': {view: true}};
    systemRolePermissions.find('Matrix').simulate('change', newValue);

    expect(changeSpy).toBeCalledWith(newValue, 'Sulu');
});

test('Call onChange callback with empty values if toggler is deactivated', () => {
    const changeSpy = jest.fn();

    const systemRolePermissions = shallow(
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

    systemRolePermissions.find('Toggler').simulate('change', false);

    expect(changeSpy).toBeCalledWith({}, 'Sulu');
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

    const systemRolePermissions = shallow(
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

    expect(systemRolePermissions.find('Matrix')).toHaveLength(0);
    systemRolePermissions.find('Toggler').simulate('change', true);
    expect(systemRolePermissions.find('Matrix')).toHaveLength(1);

    expect(systemRolePermissions.find('Matrix').prop('values')).toEqual({
        '2': {view: true, add: true, edit: true},
        '3': {view: true, add: false, edit: true},
    });
});
