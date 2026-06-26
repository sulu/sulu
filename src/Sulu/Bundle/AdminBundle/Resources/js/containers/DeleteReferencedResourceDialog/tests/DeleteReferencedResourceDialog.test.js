// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import DeleteReferencedResourceDialog from '../DeleteReferencedResourceDialog';
import type {ReferencingResourcesData} from '../../../types';

jest.mock('../../../utils/Translator');

function createReferencingResourcesData(): ReferencingResourcesData {
    return {
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
}

test('The component should render', () => {
    const onConfirm = jest.fn();
    const onCancel = jest.fn();

    const {baseElement} = render(
        <DeleteReferencedResourceDialog
            allowDeletion={true}
            confirmLoading={false}
            onCancel={onCancel}
            onConfirm={onConfirm}
            referencingResourcesData={createReferencingResourcesData()}
        />
    );

    expect(baseElement).toMatchSnapshot();
});

test('The component should render with loading state and deletion not allowed', () => {
    const onConfirm = jest.fn();
    const onCancel = jest.fn();

    const {baseElement} = render(
        <DeleteReferencedResourceDialog
            allowDeletion={false}
            confirmLoading={true}
            onCancel={onCancel}
            onConfirm={onConfirm}
            referencingResourcesData={createReferencingResourcesData()}
        />
    );

    expect(baseElement).toMatchSnapshot();
});

test('The component should call the confirm callback when the confirm button is clicked', async() => {
    const user = userEvent.setup();
    const onConfirm = jest.fn();
    const onCancel = jest.fn();

    render(
        <DeleteReferencedResourceDialog
            allowDeletion={true}
            confirmLoading={false}
            onCancel={onCancel}
            onConfirm={onConfirm}
            referencingResourcesData={createReferencingResourcesData()}
        />
    );

    expect(onConfirm).not.toHaveBeenCalled();
    await user.click(screen.getByRole('button', {name: 'sulu_admin.delete'}));
    expect(onConfirm).toHaveBeenCalled();
});

test('The component should call the cancel callback when the cancel button is clicked', async() => {
    const user = userEvent.setup();
    const onConfirm = jest.fn();
    const onCancel = jest.fn();

    render(
        <DeleteReferencedResourceDialog
            allowDeletion={true}
            confirmLoading={false}
            onCancel={onCancel}
            onConfirm={onConfirm}
            referencingResourcesData={createReferencingResourcesData()}
        />
    );

    expect(onCancel).not.toHaveBeenCalled();
    await user.click(screen.getByRole('button', {name: 'sulu_admin.cancel'}));
    expect(onCancel).toHaveBeenCalled();
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
                referencingResourcesData={createReferencingResourcesData()}
            />
        );

        expect(onCancel).not.toHaveBeenCalled();
        await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));
        expect(onCancel).toHaveBeenCalled();
    }
);
