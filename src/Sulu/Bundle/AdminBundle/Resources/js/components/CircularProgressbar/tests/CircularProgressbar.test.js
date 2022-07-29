// @flow
import {render} from '@testing-library/react';
import React from 'react';
import CircularProgressbar from '../CircularProgressbar';

test('Render a CircularProgressbar', () => {
    const {container} = render(
        <CircularProgressbar percentage={60} />
    );

    expect(container).toMatchSnapshot();
});

test('Render a CircularProgressbar without the progress info in the center', () => {
    const {container} = render(
        <CircularProgressbar hidePercentageText={true} percentage={60} />
    );

    expect(container).toMatchSnapshot();
});

test('Render a CircularProgressbar in a different size', () => {
    const {container} = render(
        <CircularProgressbar percentage={60} size={200} />
    );

    expect(container).toMatchSnapshot();
});
