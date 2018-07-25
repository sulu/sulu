// @flow
import React from 'react';
import {render} from 'enzyme';
import GhostIndicator from '../GhostIndicator';

test('Should render with given locale', () => {
    expect(render(<GhostIndicator locale="de-at" />)).toMatchSnapshot();
});
