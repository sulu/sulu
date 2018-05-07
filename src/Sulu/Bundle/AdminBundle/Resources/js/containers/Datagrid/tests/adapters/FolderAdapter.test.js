// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
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
            data={data}
            disabledIds={[]}
            loading={false}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            page={1}
            pageCount={2}
            schema={{}}
            selections={[]}
            sortColumn={undefined}
            sortOrder={undefined}
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
            data={data}
            disabledIds={[]}
            loading={false}
            onItemClick={itemClickSpy}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            page={1}
            pageCount={3}
            schema={{}}
            selections={[]}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );
    expect(folderAdapter.find('FolderList').get(0).props.onFolderClick).toBe(itemClickSpy);
});

test('Pagination should be passed correct props', () => {
    const pageChangeSpy = jest.fn();
    const folderAdapter = shallow(
        <FolderAdapter
            disabledIds={[]}
            loading={false}
            onPageChange={pageChangeSpy}
            onSort={jest.fn()}
            page={2}
            pageCount={7}
            schema={{}}
            selections={[]}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );
    expect(folderAdapter.find('Pagination').get(0).props).toEqual({
        total: 7,
        current: 2,
        loading: false,
        onChange: pageChangeSpy,
        children: expect.anything(),
    });
});
