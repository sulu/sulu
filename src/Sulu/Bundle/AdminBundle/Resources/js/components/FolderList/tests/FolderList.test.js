/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import FolderList from '../FolderList';

test('Render an empty FolderList', () => {
    const {container} = render(<FolderList />);
    expect(container).toMatchSnapshot();
});

test('Render a FolderList with Folder components inside', () => {
    const {container} = render(
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
    );
    expect(container).toMatchSnapshot();
});

test('Clicking on a folder should call the click handler with the right id as argument', async() => {
    const clickSpy = jest.fn();
    const clickedFolderId = 3;
    render(
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

    const folderList = screen.queryByText('0 Objects');
    await userEvent.click(folderList);

    expect(clickSpy).toHaveBeenCalledWith(clickedFolderId);
});
