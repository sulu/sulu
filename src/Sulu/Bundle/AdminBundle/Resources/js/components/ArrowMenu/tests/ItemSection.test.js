// @flow
import React from 'react';
import {render, mount} from 'enzyme';
import ItemSection from '../ItemSection';
import Item from '../Item';

test('Render ItemSection', () => {
    const handleChange = jest.fn();

    expect(render(
        <ItemSection
            title="Select your house"
            value={undefined}
            icon="house"
            onChange={handleChange}
        >
            <Item value="villa">Villa</Item>
            <Item value="white_house">White House</Item>
            <Item value="flat">Flat</Item>
        </ItemSection>
    )).toMatchSnapshot();
});

test('Render ItemSection with value', () => {
    const handleChange = jest.fn();

    expect(render(
        <ItemSection
            title="Select your house"
            value="flat"
            icon="house"
            onChange={handleChange}
        >
            <Item value="villa">Villa</Item>
            <Item value="white_house">White House</Item>
            <Item value="flat">Flat</Item>
        </ItemSection>
    )).toMatchSnapshot();
});

test('Handle Item click', () => {
    const handleChange = jest.fn();

    const itemSection = mount(
        <ItemSection
            title="Select your house"
            value={undefined}
            icon="house"
            onChange={handleChange}
        >
            <Item value="villa">Villa</Item>
            <Item value="white_house">White House</Item>
            <Item value="flat">Flat</Item>
        </ItemSection>
    );

    itemSection.find('Item').at(1).simulate('click');
    expect(handleChange).toBeCalledWith('white_house');
});
