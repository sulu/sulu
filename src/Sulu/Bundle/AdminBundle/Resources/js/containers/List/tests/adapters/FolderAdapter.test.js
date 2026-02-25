// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Pagination from '../../../../components/Pagination';
import listAdapterDefaultProps from '../../../../utils/TestHelper/listAdapterDefaultProps';
import getLatestMockProps from '../../../../utils/TestHelper/getLatestMockProps';
import FolderAdapter from '../../adapters/FolderAdapter';

jest.mock('../../../../components/Pagination', () => {
    return jest.fn(({children}) => <div>{children}</div>);
});

jest.mock('../../../../utils/Translator', () => ({
    translate(key) {
        switch (key) {
            case 'sulu_admin.object':
                return 'Object';
            case 'sulu_admin.objects':
                return 'Objects';
        }
    },
}));

const renderFolderAdapter = (props: Object = {}) => render(
    <FolderAdapter
        {...listAdapterDefaultProps}
        {...props}
    />
);

test('Render a basic Folder list with data', () => {
    const data = [
        {
            id: 1,
            title: 'Title 1',
            objectCount: 1,
            description: 'Description 1',
        },
        {
            id: 2,
            title: 'Title 2',
            objectCount: 0,
            description: 'Description 2',
        },
    ];
    renderFolderAdapter({
        data,
        page: 1,
        pageCount: 2,
    });

    const paginationProps: any = getLatestMockProps((Pagination: any));
    expect(paginationProps.currentPage).toEqual(1);
    expect(paginationProps.totalPages).toEqual(2);

    expect(screen.getByText('Title 1')).toBeInTheDocument();
    expect(screen.getByText('1 Object')).toBeInTheDocument();
    expect(screen.getByText('Title 2')).toBeInTheDocument();
    expect(screen.getByText('0 Objects')).toBeInTheDocument();
});

test('Click on a Folder should call the onItemEdit callback', async() => {
    const user = userEvent.setup();
    const itemClickSpy = jest.fn();
    const data = [
        {
            id: 1,
            title: 'Title 1',
            objectCount: 1,
            description: 'Description 1',
        },
        {
            id: 2,
            title: 'Title 2',
            objectCount: 7,
            description: 'Description 2',
        },
        {
            id: 3,
            title: 'Title 3',
            objectCount: 0,
            description: 'Description 3',
        },
    ];
    renderFolderAdapter({
        data,
        onItemClick: itemClickSpy,
    });

    await user.click(screen.getByRole('button', {name: /Title 3/}));

    expect(itemClickSpy).toBeCalledWith(3);
});

test('Pagination should not be rendered if no data is available', () => {
    renderFolderAdapter({
        page: 1,
    });

    expect((Pagination: any)).not.toBeCalled();
});

test('Pagination should be passed correct props', () => {
    const pageChangeSpy = jest.fn();
    const limitChangeSpy = jest.fn();
    const data = [
        {
            id: 1,
            title: 'Title 1',
            objectCount: 1,
            description: 'Description 1',
        },
        {
            id: 2,
            title: 'Title 2',
            objectCount: 7,
            description: 'Description 2',
        },
        {
            id: 3,
            title: 'Title 3',
            objectCount: 0,
            description: 'Description 3',
        },
    ];
    renderFolderAdapter({
        data,
        limit: 10,
        onLimitChange: limitChangeSpy,
        onPageChange: pageChangeSpy,
        page: 2,
        pageCount: 7,
    });

    const paginationProps: any = getLatestMockProps((Pagination: any));
    expect(paginationProps).toEqual(expect.objectContaining({
        currentLimit: 10,
        currentPage: 2,
        loading: false,
        onLimitChange: limitChangeSpy,
        onPageChange: pageChangeSpy,
        totalPages: 7,
    }));
});

test('Pagination should not be rendered if pagination is false', () => {
    renderFolderAdapter({
        limit: 10,
        page: 2,
        pageCount: 7,
        paginated: false,
    });

    expect((Pagination: any)).not.toBeCalled();
});
