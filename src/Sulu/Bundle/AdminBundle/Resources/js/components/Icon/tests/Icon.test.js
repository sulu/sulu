/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import ReactTestRenderer from 'react-test-renderer';
import Icon from '../Icon';

test('Icon should render', () => {
    expect(ReactTestRenderer.create(<Icon name="save" />)).toMatchSnapshot();
});

test('Icon should render with class names', () => {
    expect(ReactTestRenderer.create(<Icon className="test" name="edit" />)).toMatchSnapshot();
});
