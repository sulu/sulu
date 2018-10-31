// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import datagridAdapterDefaultProps from '../../../../utils/TestHelper/datagridAdapterDefaultProps';
import FolderAdapter from '../../adapters/FolderAdapter';

jest.mock('../../../../utils/Translator', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_admin.object':
                return 'Object';
            case 'sulu_admin.objects':
                return 'Objects';
        }
    },
}));

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

    const folderAdapter = render(
        <FolderAdapter
            {...datagridAdapterDefaultProps}
            data={data}
            page={1}
            pageCount={2}
        />
    );

    expect(folderAdapter).toMatchSnapshot();
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
    const folderAdapter = shallow(
        <FolderAdapter
            {...datagridAdapterDefaultProps}
            data={data}
            onItemClick={itemClickSpy}
        />
    );
    expect(folderAdapter.find('FolderList').get(0).props.onFolderClick).toBe(itemClickSpy);
});

test('Pagination should not be rendered if no data is available', () => {
    const folderAdapter = shallow(
        <FolderAdapter
            {...datagridAdapterDefaultProps}
            page={1}
        />
    );

    expect(folderAdapter.find('Pagination')).toHaveLength(0);
});

test('Pagination should be passed correct props', () => {
    const pageChangeSpy = jest.fn();
    const limitChangeSpy = jest.fn();
    const folderAdapter = shallow(
        <FolderAdapter
            {...datagridAdapterDefaultProps}
            limit={10}
            onLimitChange={limitChangeSpy}
            onPageChange={pageChangeSpy}
            page={2}
            pageCount={7}
        />
    );
    expect(folderAdapter.find('Pagination').get(0).props).toEqual({
        totalPages: 7,
        currentPage: 2,
        currentLimit: 10,
        loading: false,
        onLimitChange: limitChangeSpy,
        onPageChange: pageChangeSpy,
        children: expect.anything(),
    });
});
