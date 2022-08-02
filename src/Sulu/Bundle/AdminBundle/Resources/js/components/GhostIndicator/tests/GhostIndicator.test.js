// @flow
import React from 'react';
import {render} from '@testing-library/react';
import GhostIndicator from '../GhostIndicator';

test('Should render with given locale', () => {
    const {container} = render(<GhostIndicator locale="de-at" />);
    expect(container).toMatchSnapshot();
});

test('Should render with given locale and className', () => {
    const {container} = render(<GhostIndicator className="test" locale="de-at" />);
    expect(container).toMatchSnapshot();
});
