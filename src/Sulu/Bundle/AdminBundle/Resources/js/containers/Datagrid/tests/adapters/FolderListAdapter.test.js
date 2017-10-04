/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render, shallow} from 'enzyme';
import FolderListAdapter from '../../adapters/FolderListAdapter';

test('Render a basic FolderList with data', () => {
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
    const folderListAdapter = render(<FolderListAdapter data={data} />);

    expect(folderListAdapter).toMatchSnapshot();
});

test('Click on a Folder should call the onItemEdit callback', () => {
    const itemEditClickSpy = jest.fn();
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
    const folderListAdapter = shallow(<FolderListAdapter data={data} onItemEditClick={itemEditClickSpy} />);
    expect(folderListAdapter.find('FolderList').get(0).props.onFolderClick).toBe(itemEditClickSpy);
});
