/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render, shallow} from 'enzyme';
import Folder from '../Folder';

test('Render a Folder component', () => {
    expect(render(
        <Folder
            id="1"
            info="3 Objects"
            title="This is a folder"
        />
    )).toMatchSnapshot();
});

test('Call clickhandler when clicking on the folder', () => {
    const clickSpy = jest.fn();
    const folderId = 1;
    const folder = shallow(
        <Folder
            id={folderId}
            info="3 Objects"
            onClick={clickSpy}
            title="This is a folder"
        />
    );

    folder.simulate('click');
    expect(clickSpy).toHaveBeenCalledWith(folderId);
});
