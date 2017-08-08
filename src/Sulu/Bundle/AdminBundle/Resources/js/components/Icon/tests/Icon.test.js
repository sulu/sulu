/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render} from 'enzyme';
import Icon from '../Icon';

test('Icon should render', () => {
    expect(render(<Icon name="save" />)).toMatchSnapshot();
});

test('Icon should render with class names', () => {
    expect(render(<Icon className="test" name="edit" />)).toMatchSnapshot();
});
