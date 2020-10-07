// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import Folder from '../Folder';

test('Render a Folder component', () => {
    expect(render(
        <Folder
            hasPermissions={false}
            id="1"
            info="3 Objects"
            title="This is a folder"
        />
    )).toMatchSnapshot();
});

test('Use permission icon if hasPermissions flag is set', () => {
    const folder = shallow(
        <Folder
            hasPermissions={true}
            id="1"
            info="3 Objects"
            title="This is a folder"
        />
    );

    expect(folder.find('Icon').prop('name')).toEqual('su-folder-permission');
});

test('Call clickhandler when clicking on the folder', () => {
    const clickSpy = jest.fn();
    const folderId = 1;
    const folder = shallow(
        <Folder
            hasPermissions={false}
            id={folderId}
            info="3 Objects"
            onClick={clickSpy}
            title="This is a folder"
        />
    );

    folder.simulate('click');
    expect(clickSpy).toHaveBeenCalledWith(folderId);
});
