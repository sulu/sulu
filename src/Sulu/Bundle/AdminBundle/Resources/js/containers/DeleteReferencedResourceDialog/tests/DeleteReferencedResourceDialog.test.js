// @flow
import React from 'react';
import {render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import DeleteReferencedResourceDialog from '../DeleteReferencedResourceDialog';
import {translate} from '../../../utils/Translator';
import type {ReferencingResourcesData} from '../../../types';

jest.mock('../../../utils/Translator');

beforeEach(() => {
    (translate: any).mockClear();
    (translate: any).mockImplementation((key) => key);
});

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

test('The component should name the referenced resource and show the type of the referencing resources', () => {
    (translate: any).mockImplementation((key) => {
        return {
            'sulu_reference.resource.pages': 'Page',
            'sulu_reference.resource.snippets': 'Snippet',
        }[key] || key;
    });

    const referencingResourcesData: ReferencingResourcesData = {
        referencingResources: [
            {id: 2, resourceKey: 'pages', title: 'Team'},
            {id: 3, resourceKey: 'snippets', title: 'Footer'},
            {id: 4, resourceKey: 'articles', title: 'News'},
        ],
        referencingResourcesCount: 3,
        resource: {
            id: 1,
            resourceKey: 'media',
            title: 'Photo',
        },
    };

    render(
        <DeleteReferencedResourceDialog
            allowDeletion={true}
            confirmLoading={false}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            referencingResourcesData={referencingResourcesData}
        />
    );

    expect(translate).toHaveBeenCalledWith('sulu_admin.delete_linked_warning_text_with_title', {title: 'Photo'});
    // a resource without a translated type is listed with its title only
    expect(screen.getAllByRole('listitem').map((item) => item.textContent))
        .toEqual(['Team (Page)', 'Footer (Snippet)', 'News']);
});

test('The component should use the generic text if the referenced resource has no title', () => {
    const referencingResourcesData: ReferencingResourcesData = {
        referencingResources: [
            {id: 2, resourceKey: 'pages', title: 'Team'},
        ],
        referencingResourcesCount: 1,
        resource: {
            id: 1,
            resourceKey: 'pages',
        },
    };

    render(
        <DeleteReferencedResourceDialog
            allowDeletion={true}
            confirmLoading={false}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            referencingResourcesData={referencingResourcesData}
        />
    );

    expect(screen.getByText('sulu_admin.delete_linked_warning_text')).toBeInTheDocument();
    expect(translate).not.toHaveBeenCalledWith(
        'sulu_admin.delete_linked_warning_text_with_title',
        expect.anything()
    );
});

test('The component should list the referencing resources for each referenced resource', () => {
    const referencingResourcesData: Array<ReferencingResourcesData> = [
        {
            referencingResources: [
                {id: 2, resourceKey: 'pages', title: 'Team'},
                {id: 3, resourceKey: 'pages', title: 'About us'},
            ],
            referencingResourcesCount: 2,
            resource: {id: 1, resourceKey: 'media', title: 'Photo'},
        },
        {
            referencingResources: [
                {id: 2, resourceKey: 'pages', title: 'Team'},
            ],
            referencingResourcesCount: 1,
            resource: {id: 5, resourceKey: 'media', title: 'Logo'},
        },
    ];

    render(
        <DeleteReferencedResourceDialog
            allowDeletion={true}
            confirmLoading={false}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            referencingResourcesData={referencingResourcesData}
        />
    );

    expect(screen.getByText('sulu_admin.delete_linked_warning_text_multiple')).toBeInTheDocument();
    expect(translate).toHaveBeenCalledWith('sulu_admin.delete_linked_resource_text', {title: 'Photo'});
    expect(translate).toHaveBeenCalledWith('sulu_admin.delete_linked_resource_text', {title: 'Logo'});

    const lists = screen.getAllByRole('list');
    expect(lists).toHaveLength(2);
    expect(within(lists[0]).getAllByRole('listitem').map((item) => item.textContent)).toEqual(['Team', 'About us']);
    expect(within(lists[1]).getAllByRole('listitem').map((item) => item.textContent)).toEqual(['Team']);
});

test('The component should show the abort text for multiple referenced resources if deletion is not allowed', () => {
    const referencingResourcesData: Array<ReferencingResourcesData> = [
        {
            referencingResources: [{id: 2, resourceKey: 'pages', title: 'Team'}],
            referencingResourcesCount: 1,
            resource: {id: 1, resourceKey: 'media', title: 'Photo'},
        },
        {
            referencingResources: [{id: 3, resourceKey: 'pages', title: 'About us'}],
            referencingResourcesCount: 1,
            resource: {id: 5, resourceKey: 'media', title: 'Logo'},
        },
    ];

    render(
        <DeleteReferencedResourceDialog
            allowDeletion={false}
            confirmLoading={false}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            referencingResourcesData={referencingResourcesData}
        />
    );

    expect(screen.getByText('sulu_admin.delete_linked_abort_text')).toBeInTheDocument();
    expect(translate).not.toHaveBeenCalledWith('sulu_admin.delete_linked_warning_text_multiple');
});
