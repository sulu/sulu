// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
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

    render(
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

    expect(screen.queryByRole('table')).not.toBeInTheDocument();
    expect(screen.getByRole('checkbox')).not.toBeChecked();
});

test('Render permissions for a single system in disabled state', () => {
    render(
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

    expect(screen.getByRole('table')).toHaveClass('disabled');
});

test('Call onChange callback when matrix changes', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const roles = [
        {id: 1, identifier: '', name: 'User', permissions: [], system: 'Sulu'},
    ];

    render(
        <SystemRolePermissions
            actions={['view']}
            disabled={false}
            onChange={changeSpy}
            resourceKey="test"
            roles={roles}
            system="Sulu"
            values={{'1': {view: true}}}
        />
    );

    await user.click(screen.getByTitle('View'));

    const newValue = {'1': {view: false}};

    expect(changeSpy).toHaveBeenCalledWith(newValue, 'Sulu');
});

test('Call onChange callback with empty values if toggler is deactivated', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    render(
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

    await user.click(screen.getByRole('checkbox'));

    expect(changeSpy).toHaveBeenCalledWith({}, 'Sulu');
});

test('Show default values after activating toggler', async() => {
    const user = userEvent.setup();
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

    render(
        <SystemRolePermissions
            actions={['view', 'add', 'edit']}
            disabled={false}
            onChange={changeSpy}
            resourceKey="test"
            roles={roles}
            system="Sulu"
            values={{}}
        />
    );

    expect(screen.queryByRole('table')).not.toBeInTheDocument();
    await user.click(screen.getByRole('checkbox'));
    expect(screen.getByRole('table')).toBeInTheDocument();

    expect(screen.getAllByTitle('View')[0]).toHaveClass('selected');
    expect(screen.getAllByTitle('Add')[0]).toHaveClass('selected');
    expect(screen.getAllByTitle('Edit')[0]).toHaveClass('selected');
    expect(screen.getAllByTitle('View')[1]).toHaveClass('selected');
    expect(screen.getAllByTitle('Add')[1]).not.toHaveClass('selected');
    expect(screen.getAllByTitle('Edit')[1]).toHaveClass('selected');
});
