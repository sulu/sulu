// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import CardCollection from '../CardCollection';

test('Render passed values with renderCardContent callback', () => {
    const value = [
        {
            name: 'Test 1',
        },
        {
            name: 'Test 2',
        },
    ];

    const renderCardContent = jest.fn((cardData) => (
        <span>{cardData.name}</span>
    ));

    expect(render(
        <CardCollection onChange={jest.fn()} renderCardContent={renderCardContent} value={value} />
    )).toMatchSnapshot();
});

test('Removing a card should call the onChange callback', () => {
    const changeSpy = jest.fn();

    const value = [
        {
            name: 'Test 1',
        },
        {
            name: 'Test 2',
        },
    ];

    const renderCardContent = jest.fn((cardData) => (
        <span>{cardData.name}</span>
    ));

    const cardCollection = shallow(
        <CardCollection onChange={changeSpy} renderCardContent={renderCardContent} value={value} />
    );

    cardCollection.find('Card').at(0).simulate('remove', 0);

    expect(changeSpy).toBeCalledWith([{name: 'Test 2'}]);
});
