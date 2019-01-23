// @flow
import React from 'react';
import {render} from 'enzyme';
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

    expect(render(<CardCollection renderCardContent={renderCardContent} value={value} />)).toMatchSnapshot();
});
