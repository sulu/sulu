/* eslint-disable flowtype/require-valid-file-annotation */
import Icon from '../Icon';
import React from 'react';
import {render} from 'enzyme';

test('Icon should render', () => {
    expect(render(<Icon name="save" />)).toMatchSnapshot();
});

test('Icon should render with class names', () => {
    expect(render(<Icon className="test" name="edit" />)).toMatchSnapshot();
});
