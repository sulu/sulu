// @flow
import React from 'react';
import {shallow, render} from 'enzyme';
import SystemRolePermissions from '../SystemRolePermissions';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Render permissions for a single system', () => {
    const roles = [
        {id: 2, identifier: '', name: 'User', permissions: [], system: 'Sulu'},
        {id: 3, identifier: '', name: 'Contact Manager', permissions: [], system: 'Sulu'},
    ];

    expect(render(
        <SystemRolePermissions
            actions={['view', 'add', 'edit']}
            disabled={false}
            onChange={jest.fn()}
            roles={roles}
            system="Sulu"
            values={{'2': {view: true, add: false, edit: true}, '3': {view: false, add: true, edit: false}}}
        />
    )).toMatchSnapshot();
});

test('Render permissions for a single system in disabled state', () => {
    const systemRolePermissions = shallow(
        <SystemRolePermissions
            actions={[]}
            disabled={true}
            onChange={jest.fn()}
            roles={[]}
            system="Sulu"
            values={{}}
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
            roles={[]}
            system="Sulu"
            values={{}}
        />
    );

    const newValue = {'1': {view: true}};
    systemRolePermissions.find('Matrix').simulate('change', newValue);

    expect(changeSpy).toBeCalledWith(newValue);
});
