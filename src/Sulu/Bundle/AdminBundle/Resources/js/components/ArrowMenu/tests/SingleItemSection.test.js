// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import SingleItemSection from '../SingleItemSection';
import Item from '../Item';

test('Render ItemSection', () => {
    const {container} = render(
        <SingleItemSection
            icon="fa-home"
            onChange={jest.fn()}
            title="Select your house"
            value={undefined}
        >
            <Item value="villa">Villa</Item>
            <Item value="white_house">White House</Item>
            <Item value="flat">Flat</Item>
        </SingleItemSection>
    );

    expect(container).toMatchSnapshot();
});

test('Render ItemSection with value', () => {
    const {container} = render(
        <SingleItemSection
            icon="fa-home"
            onChange={jest.fn()}
            title="Select your house"
            value="flat"
        >
            <Item value="villa">Villa</Item>
            <Item value="white_house">White House</Item>
            <Item value="flat">Flat</Item>
        </SingleItemSection>
    );

    expect(container).toMatchSnapshot();
});

test('Handle Item click', async() => {
    const handleChange = jest.fn();
    render(
        <SingleItemSection
            icon="fa-home"
            onChange={handleChange}
            title="Select your house"
            value={undefined}
        >
            <Item value="villa">Villa</Item>
            <Item value="white_house">White House</Item>
            <Item value="flat">Flat</Item>
        </SingleItemSection>
    );

    const user = userEvent.setup();
    await user.click(screen.getByText('White House'));

    expect(handleChange).toBeCalledWith('white_house');
});
