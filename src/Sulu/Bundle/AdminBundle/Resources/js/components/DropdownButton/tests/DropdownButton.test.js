// @flow
import React from 'react';
import {fireEvent, render, screen} from '@testing-library/react';
import DropdownButton from '../DropdownButton';

test('Render dropdown button', () => {
    const {container} = render(
        <DropdownButton icon="su-plus" label="Add">
            <DropdownButton.Item onClick={jest.fn()}>Option 1</DropdownButton.Item>
        </DropdownButton>
    );

    expect(container).toMatchSnapshot();
});

test('Clicking dropdown items should call the corresponding callback', () => {
    const option1ClickSpy = jest.fn();
    const option2ClickSpy = jest.fn();

    render(
        <DropdownButton icon="su-plus" label="Add">
            <DropdownButton.Item onClick={option1ClickSpy}>Option 1</DropdownButton.Item>
            <DropdownButton.Item onClick={option2ClickSpy}>Option 2</DropdownButton.Item>
        </DropdownButton>
    );

    const dropdownButton = screen.queryByText('Add');
    fireEvent.click(dropdownButton);

    const option1 = screen.queryByText('Option 1');
    fireEvent.click(option1);

    expect(option1ClickSpy).toBeCalled();
    expect(option2ClickSpy).not.toBeCalled();

    option1ClickSpy.mockReset();
    option2ClickSpy.mockReset();

    fireEvent.click(dropdownButton);
    const option2 = screen.queryByText('Option 2');
    fireEvent.click(option2);

    expect(option1ClickSpy).not.toBeCalled();
    expect(option2ClickSpy).toBeCalled();
});
