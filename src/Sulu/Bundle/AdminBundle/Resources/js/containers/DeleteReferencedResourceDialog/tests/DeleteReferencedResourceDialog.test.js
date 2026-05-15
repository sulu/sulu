// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import DeleteReferencedResourceDialog from '../DeleteReferencedResourceDialog';
import type {ReferencingResourcesData} from '../../../types';

const referencingResourcesData: ReferencingResourcesData = {
    referencingResources: [
        {id: 2, resourceKey: 'pages', title: 'Foo'},
        {id: 3, resourceKey: 'pages', title: 'Bar'},
    ],
    referencingResourcesCount: 2,
    resource: {
        id: 1,
        resourceKey: 'pages',
    },
};

beforeEach(() => {
    jest.clearAllMocks();
});

test('The component should render', () => {
    render(
        <DeleteReferencedResourceDialog
            allowDeletion={true}
            confirmLoading={false}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            referencingResourcesData={referencingResourcesData}
        />
    );

    expect(screen.getByText('sulu_admin.delete_linked_warning_title').closest('section')).toMatchSnapshot();
    expect(screen.getByText('sulu_admin.delete_linked_warning_title')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeEnabled();
    expect(screen.getByRole('button', {name: 'sulu_admin.cancel'})).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.delete_linked_warning_text')).toBeInTheDocument();
    expect(screen.getByText('Foo')).toBeInTheDocument();
    expect(screen.getByText('Bar')).toBeInTheDocument();
});

test('The component should render with loading state and deletion not allowed', () => {
    render(
        <DeleteReferencedResourceDialog
            allowDeletion={false}
            confirmLoading={true}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            referencingResourcesData={referencingResourcesData}
        />
    );

    expect(screen.getByText('sulu_admin.item_not_deletable')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.ok'})).toBeDisabled();
    expect(screen.queryByRole('button', {name: 'sulu_admin.cancel'})).not.toBeInTheDocument();
    expect(screen.getByText('sulu_admin.delete_linked_abort_text')).toBeInTheDocument();
});

test('The component should call the confirm callback when the confirm button is clicked', async() => {
    const user = userEvent.setup();
    const onConfirm = jest.fn();

    render(
        <DeleteReferencedResourceDialog
            allowDeletion={true}
            confirmLoading={false}
            onCancel={jest.fn()}
            onConfirm={onConfirm}
            referencingResourcesData={referencingResourcesData}
        />
    );

    expect(onConfirm).not.toBeCalled();
    await user.click(screen.getByRole('button', {name: 'sulu_admin.delete'}));
    expect(onConfirm).toBeCalled();
});

test('The component should call the cancel callback when the cancel button is clicked', async() => {
    const user = userEvent.setup();
    const onCancel = jest.fn();

    render(
        <DeleteReferencedResourceDialog
            allowDeletion={true}
            confirmLoading={false}
            onCancel={onCancel}
            onConfirm={jest.fn()}
            referencingResourcesData={referencingResourcesData}
        />
    );

    expect(onCancel).not.toBeCalled();
    await user.click(screen.getByRole('button', {name: 'sulu_admin.cancel'}));
    expect(onCancel).toBeCalled();
});

test(
    'The component should call the cancel callback when the confirm button is clicked while deletion is not allowed',
    async() => {
        const user = userEvent.setup();
        const onConfirm = jest.fn();
        const onCancel = jest.fn();

        render(
            <DeleteReferencedResourceDialog
                allowDeletion={false}
                confirmLoading={false}
                onCancel={onCancel}
                onConfirm={onConfirm}
                referencingResourcesData={referencingResourcesData}
            />
        );

        expect(onCancel).not.toBeCalled();
        await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));
        expect(onCancel).toBeCalled();
        expect(onConfirm).not.toBeCalled();
    }
);
