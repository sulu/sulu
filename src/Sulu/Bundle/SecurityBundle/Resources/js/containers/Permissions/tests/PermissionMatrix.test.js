// @flow
import {render, screen} from '@testing-library/react';
import React from 'react';
import {Matrix} from 'sulu-admin-bundle/components';
import PermissionMatrix from '../PermissionMatrix';
import type {MatrixValues} from 'sulu-admin-bundle/components/Matrix/types';
import type {ContextPermission} from '../types';
import type {SecurityContexts} from '../../../stores/securityContextStore/types';

jest.mock('sulu-admin-bundle/components', () => {
    const MatrixMock = jest.fn(() => null);
    (MatrixMock: any).Row = jest.fn(() => null);
    (MatrixMock: any).Item = jest.fn(() => null);

    return {
        Matrix: MatrixMock,
    };
});

beforeEach(() => {
    jest.clearAllMocks();
});

function createContextPermissions(): Array<ContextPermission> {
    return [
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
}

function createSecurityContexts(): SecurityContexts {
    return {
        'sulu.contact.people': ['view', 'add', 'edit', 'delete'],
        'sulu.contact.organizations': ['view', 'add', 'edit', 'delete'],
    };
}

test('Render with minimal', () => {
    render(
        <PermissionMatrix
            contextPermissions={createContextPermissions()}
            onChange={jest.fn()}
            securityContexts={createSecurityContexts()}
        />
    );

    expect(Matrix).toHaveBeenCalledTimes(1);
    const [matrixProps] = (Matrix: any).mock.calls[0];
    expect(matrixProps.disabled).toEqual(false);
    expect(matrixProps.values).toEqual({
        'sulu.contact.people': {
            'view': true,
            'delete': true,
            'add': true,
            'edit': true,
        },
        'sulu.contact.organizations': {
            'view': true,
            'delete': true,
            'add': true,
            'edit': true,
        },
    });
});

test('Render in disabled state', () => {
    render(
        <PermissionMatrix
            contextPermissions={createContextPermissions()}
            disabled={true}
            onChange={jest.fn()}
            securityContexts={createSecurityContexts()}
        />
    );

    const [matrixProps] = (Matrix: any).mock.calls[0];
    expect(matrixProps.disabled).toEqual(true);
});

test('Render with title', () => {
    render(
        <PermissionMatrix
            contextPermissions={createContextPermissions()}
            onChange={jest.fn()}
            securityContexts={createSecurityContexts()}
            title="Contact"
        />
    );

    expect(screen.getByRole('heading', {level: 2, name: 'Contact'})).toBeInTheDocument();
});

test('Render with subTitle', () => {
    render(
        <PermissionMatrix
            contextPermissions={createContextPermissions()}
            onChange={jest.fn()}
            securityContexts={createSecurityContexts()}
            subTitle="Contact"
        />
    );

    expect(screen.getByRole('heading', {level: 3, name: 'Contact'})).toBeInTheDocument();
});

test('Should trigger onChange correctly', () => {
    const onChange = jest.fn();

    render(
        <PermissionMatrix
            contextPermissions={createContextPermissions()}
            onChange={onChange}
            securityContexts={createSecurityContexts()}
        />
    );

    const matrixValues: MatrixValues = {
        'sulu.contact.people': {
            'view': true,
            'delete': true,
            'add': true,
            'edit': false,
        },
    };
    const [matrixProps] = (Matrix: any).mock.calls[0];
    matrixProps.onChange(matrixValues);

    expect(onChange).toBeCalledWith([
        {
            id: 1,
            context: 'sulu.contact.people',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': false,
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
    ]);
});
