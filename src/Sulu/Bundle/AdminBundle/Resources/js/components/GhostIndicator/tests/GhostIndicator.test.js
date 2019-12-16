// @flow
import React from 'react';
import {render} from 'enzyme';
import GhostIndicator from '../GhostIndicator';

test('Should render with given locale', () => {
    expect(render(<GhostIndicator locale="de-at" />)).toMatchSnapshot();
});

test('Should render with given locale and className', () => {
    expect(render(<GhostIndicator className="test" locale="de-at" />)).toMatchSnapshot();
});
