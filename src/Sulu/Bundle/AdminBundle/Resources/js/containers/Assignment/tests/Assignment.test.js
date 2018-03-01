// @flow
import React from 'react';
import {render} from 'enzyme';
import Assignment from '../Assignment';

test('Show with default plus icon', () => {
    expect(render(<Assignment />)).toMatchSnapshot();
});

test('Show with passed icon', () => {
    expect(render(<Assignment icon="su-document" />)).toMatchSnapshot();
});
