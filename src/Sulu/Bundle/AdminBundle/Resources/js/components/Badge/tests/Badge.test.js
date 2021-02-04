// @flow
import React from 'react';
import {render} from 'enzyme';
import Badge from '../Badge';

test('Render a badge', () => {
    expect(render(<Badge>Hello world</Badge>)).toMatchSnapshot();
});
