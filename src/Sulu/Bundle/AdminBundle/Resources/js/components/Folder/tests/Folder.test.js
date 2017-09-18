/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render, shallow} from 'enzyme';
import Folder from '../Folder';

test('Render a Folder component', () => {
    const handleClick = jest.fn();

    expect(render(
        <Folder
            id="1"
            meta="3 Objects"
            title="This is a folder"
            onClick={handleClick}
        />
    )).toMatchSnapshot();
});

test('Should call clickhandler when clicking on folder', () => {
    const clickSpy = jest.fn();
    const folder = shallow(
        <Folder
            id="1"
            meta="3 Objects"
            title="This is a folder"
            onClick={clickSpy}
        />
    );

    folder.simulate('click');
    expect(clickSpy).toBeCalled();
});
