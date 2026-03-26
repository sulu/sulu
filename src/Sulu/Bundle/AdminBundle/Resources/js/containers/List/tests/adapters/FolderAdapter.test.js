// @flow
import React from 'react';
import {render} from '@testing-library/react';
import FolderList from '../../../../components/FolderList';
import Pagination from '../../../../components/Pagination';
import listAdapterDefaultProps from '../../../../utils/TestHelper/listAdapterDefaultProps';
import FolderAdapter from '../../adapters/FolderAdapter';

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

jest.mock('../../../../components/FolderList', () => {
    const FolderListMock: any = jest.fn(({children}) => <div>{children}</div>);
    FolderListMock.Folder = jest.fn(() => null);

    return FolderListMock;
});

jest.mock('../../../../components/Pagination', () => jest.fn(({children}) => <div>{children}</div>));

function getLatestFolderListProps() {
    const calls = (FolderList: any).mock.calls;
    return calls[calls.length - 1][0];
}

function getFolderProps(index: number) {
    return (FolderList.Folder: any).mock.calls[index][0];
}

function getLatestPaginationProps() {
    const calls = (Pagination: any).mock.calls;
    return calls[calls.length - 1][0];
}

beforeEach(() => {
    jest.clearAllMocks();
});

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

    render(
        <FolderAdapter
            {...listAdapterDefaultProps}
            data={data}
            page={1}
            pageCount={2}
        />
    );

    expect((FolderList.Folder: any).mock.calls).toHaveLength(2);
    expect(getFolderProps(0)).toEqual(expect.objectContaining({
        id: 1,
        info: '1 Object',
        title: 'Title 1',
    }));
    expect(getFolderProps(1)).toEqual(expect.objectContaining({
        id: 2,
        info: '0 Objects',
        title: 'Title 2',
    }));

    expect(getLatestPaginationProps()).toEqual(expect.objectContaining({
        currentPage: 1,
        totalPages: 2,
    }));
});

test('Click on a Folder should call the onItemEdit callback', () => {
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
    render(
        <FolderAdapter
            {...listAdapterDefaultProps}
            data={data}
            onItemClick={itemClickSpy}
        />
    );

    expect(getLatestFolderListProps().onFolderClick).toBe(itemClickSpy);
});

test('Pagination should not be rendered if no data is available', () => {
    render(
        <FolderAdapter
            {...listAdapterDefaultProps}
            page={1}
        />
    );

    expect((Pagination: any).mock.calls).toHaveLength(0);
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

    render(
        <FolderAdapter
            {...listAdapterDefaultProps}
            data={data}
            limit={10}
            onLimitChange={limitChangeSpy}
            onPageChange={pageChangeSpy}
            page={2}
            pageCount={7}
        />
    );
    expect(getLatestPaginationProps()).toEqual({
        totalPages: 7,
        currentPage: 2,
        currentLimit: 10,
        loading: false,
        onLimitChange: limitChangeSpy,
        onPageChange: pageChangeSpy,
        children: expect.anything(),
    });
});

test('Pagination should not be rendered if pagination is false', () => {
    render(
        <FolderAdapter
            {...listAdapterDefaultProps}
            limit={10}
            page={2}
            pageCount={7}
            paginated={false}
        />
    );
    expect((Pagination: any).mock.calls).toHaveLength(0);
});
