// @flow
import React from 'react';
import {render, mount} from 'enzyme';
import SingleItemSection from '../SingleItemSection';
import Item from '../Item';

test('Render ItemSection', () => {
    const handleChange = jest.fn();

    expect(render(
        <SingleItemSection
            title="Select your house"
            value={undefined}
            icon="fa-home"
            onChange={handleChange}
        >
            <Item value="villa">Villa</Item>
            <Item value="white_house">White House</Item>
            <Item value="flat">Flat</Item>
        </SingleItemSection>
    )).toMatchSnapshot();
});

test('Render ItemSection with value', () => {
    const handleChange = jest.fn();

    expect(render(
        <SingleItemSection
            title="Select your house"
            value="flat"
            icon="fa-home"
            onChange={handleChange}
        >
            <Item value="villa">Villa</Item>
            <Item value="white_house">White House</Item>
            <Item value="flat">Flat</Item>
        </SingleItemSection>
    )).toMatchSnapshot();
});

test('Handle Item click', () => {
    const handleChange = jest.fn();

    const itemSection = mount(
        <SingleItemSection
            title="Select your house"
            value={undefined}
            icon="fa-home"
            onChange={handleChange}
        >
            <Item value="villa">Villa</Item>
            <Item value="white_house">White House</Item>
            <Item value="flat">Flat</Item>
        </SingleItemSection>
    );

    itemSection.find('Item').at(1).simulate('click');
    expect(handleChange).toBeCalledWith('white_house');
});
