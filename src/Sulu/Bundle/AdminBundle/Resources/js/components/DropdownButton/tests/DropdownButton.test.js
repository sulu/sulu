// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import DropdownButton from '../DropdownButton';

test('Render dropdown button', () => {
    expect(render(
        <DropdownButton icon="su-plus" label="Add">
            <DropdownButton.Item onClick={jest.fn()}>Option 1</DropdownButton.Item>
        </DropdownButton>
    )).toMatchSnapshot();
});

test('Clicking dropdown items should call the corresponding callback', () => {
    const option1ClickSpy = jest.fn();
    const option2ClickSpy = jest.fn();

    const dropdownButton = mount(
        <DropdownButton icon="su-plus" label="Add">
            <DropdownButton.Item onClick={option1ClickSpy}>Option 1</DropdownButton.Item>
            <DropdownButton.Item onClick={option2ClickSpy}>Option 2</DropdownButton.Item>
        </DropdownButton>
    );

    dropdownButton.find('Button').simulate('click');
    dropdownButton.find('Action').at(0).simulate('click');
    expect(option1ClickSpy).toBeCalled();
    expect(option2ClickSpy).not.toBeCalled();

    option1ClickSpy.mockReset();
    option2ClickSpy.mockReset();

    dropdownButton.find('Button').simulate('click');
    dropdownButton.find('Action').at(1).simulate('click');
    expect(option1ClickSpy).not.toBeCalled();
    expect(option2ClickSpy).toBeCalled();
});
