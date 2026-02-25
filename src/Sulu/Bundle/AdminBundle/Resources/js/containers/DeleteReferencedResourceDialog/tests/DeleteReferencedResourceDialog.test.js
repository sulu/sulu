// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import DeleteReferencedResourceDialog from '../DeleteReferencedResourceDialog';
import type {ReferencingResourcesData} from '../../../types';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

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
    const {baseElement} = render(
        <DeleteReferencedResourceDialog
            allowDeletion={true}
            confirmLoading={false}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            referencingResourcesData={referencingResourcesData}
        />
    );

    expect(baseElement).toMatchSnapshot();
});

test('The component should render with loading state and deletion not allowed', () => {
    const {baseElement} = render(
        <DeleteReferencedResourceDialog
            allowDeletion={false}
            confirmLoading={true}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            referencingResourcesData={referencingResourcesData}
        />
    );

    expect(baseElement).toMatchSnapshot();
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
    }
);
