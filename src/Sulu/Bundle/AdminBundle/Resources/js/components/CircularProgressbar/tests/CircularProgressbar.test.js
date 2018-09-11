// @flow
import {render} from 'enzyme';
import React from 'react';
import CircularProgressbar from '../CircularProgressbar';

test('Render a CircularProgressbar', () => {
    expect(render(
        <CircularProgressbar percentage={60} />
    )).toMatchSnapshot();
});

test('Render a CircularProgressbar without the progress info in the center', () => {
    expect(render(
        <CircularProgressbar hidePercentageText={true} percentage={60} />
    )).toMatchSnapshot();
});

test('Render a CircularProgressbar in a different size', () => {
    expect(render(
        <CircularProgressbar percentage={60} size={200} />
    )).toMatchSnapshot();
});
