// @flow
import {mount} from 'enzyme';
import React from 'react';
import DeleteReferencedResourceDialog from '../DeleteReferencedResourceDialog';
import type {ReferencingResourcesData} from '../../../types';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('The component should render', () => {
    const onConfirm = jest.fn();
    const onCancel = jest.fn();
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

    const view = mount(
        <DeleteReferencedResourceDialog
            allowDeletion={true}
            confirmLoading={false}
            onCancel={onCancel}
            onConfirm={onConfirm}
            referencingResourcesData={referencingResourcesData}
        />
    );

    expect(view.find('Dialog > Portal').at(0).render()).toMatchSnapshot();
});

test('The component should render with loading state and deletion not allowed', () => {
    const onConfirm = jest.fn();
    const onCancel = jest.fn();
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

    const view = mount(
        <DeleteReferencedResourceDialog
            allowDeletion={false}
            confirmLoading={true}
            onCancel={onCancel}
            onConfirm={onConfirm}
            referencingResourcesData={referencingResourcesData}
        />
    );

    expect(view.find('Dialog > Portal').at(0).render()).toMatchSnapshot();
});

test('The component should call the confirm callback when the confirm button is clicked', () => {
    const onConfirm = jest.fn();
    const onCancel = jest.fn();
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

    const view = mount(
        <DeleteReferencedResourceDialog
            allowDeletion={true}
            confirmLoading={false}
            onCancel={onCancel}
            onConfirm={onConfirm}
            referencingResourcesData={referencingResourcesData}
        />
    );

    expect(onConfirm).not.toBeCalled();
    view.find('Button[skin="primary"]').simulate('click');
    expect(onConfirm).toBeCalled();
});

test('The component should call the cancel callback when the cancel button is clicked', () => {
    const onConfirm = jest.fn();
    const onCancel = jest.fn();
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

    const view = mount(
        <DeleteReferencedResourceDialog
            allowDeletion={true}
            confirmLoading={false}
            onCancel={onCancel}
            onConfirm={onConfirm}
            referencingResourcesData={referencingResourcesData}
        />
    );

    expect(onCancel).not.toBeCalled();
    view.find('Button[skin="secondary"]').simulate('click');
    expect(onCancel).toBeCalled();
});

test(
    'The component should call the cancel callback when the confirm button is clicked while deletion is not allowed',
    () => {
        const onConfirm = jest.fn();
        const onCancel = jest.fn();
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

        const view = mount(
            <DeleteReferencedResourceDialog
                allowDeletion={false}
                confirmLoading={false}
                onCancel={onCancel}
                onConfirm={onConfirm}
                referencingResourcesData={referencingResourcesData}
            />
        );

        expect(onCancel).not.toBeCalled();
        view.find('Button[skin="primary"]').simulate('click');
        expect(onCancel).toBeCalled();
    }
);
