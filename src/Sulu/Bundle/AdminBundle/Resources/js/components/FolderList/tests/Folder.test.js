// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Folder from '../Folder';

test('Render a Folder component', () => {
    const {container} = render(
        <Folder
            hasPermissions={false}
            id="1"
            info="3 Objects"
            title="This is a folder"
        />
    );
    expect(container).toMatchSnapshot();
});

test('Use permission icon if hasPermissions flag is set', () => {
    render(
        <Folder
            hasPermissions={true}
            id="1"
            info="3 Objects"
            title="This is a folder"
        />
    );

    const icon = screen.queryByLabelText('su-folder-permission');
    expect(icon).toBeInTheDocument();
});

test('Call clickhandler when clicking on the folder', async() => {
    const clickSpy = jest.fn();
    const folderId = 1;
    render(
        <Folder
            hasPermissions={false}
            id={folderId}
            info="3 Objects"
            onClick={clickSpy}
            title="This is a folder"
        />
    );

    const folder = screen.queryByText('This is a folder');
    await userEvent.click(folder);

    expect(clickSpy).toHaveBeenCalledWith(folderId);
});
