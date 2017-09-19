/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render, mount} from 'enzyme';
import FolderList from '../FolderList';

test('Render an empty FolderList', () => {
    expect(render(<FolderList />)).toMatchSnapshot();
});

test('Render a FolderList with Folder components inside', () => {
    expect(render(
        <FolderList>
            <FolderList.Folder
                id="1"
                info="3 Objects"
                title="This is a folder"
            />
            <FolderList.Folder
                id="2"
                info="2 Objects"
                title="This is a folder"
            />
            <FolderList.Folder
                id="3"
                info="0 Objects"
                title="This is a folder"
            />
        </FolderList>
    )).toMatchSnapshot();
});

test('Clicking on a folder should call the click handler with the right id as argument', () => {
    const clickSpy = jest.fn();
    const clickedFolderId = 3;
    const folderList = mount(
        <FolderList onFolderClick={clickSpy}>
            <FolderList.Folder
                id="1"
                info="3 Objects"
                title="This is a folder"
            />
            <FolderList.Folder
                id="2"
                info="2 Objects"
                title="This is a folder"
            />
            <FolderList.Folder
                id={clickedFolderId}
                info="0 Objects"
                title="This is a folder"
            />
        </FolderList>
    );

    folderList.find('.folder').at(2).simulate('click');
    expect(clickSpy).toHaveBeenCalledWith(clickedFolderId);
});
