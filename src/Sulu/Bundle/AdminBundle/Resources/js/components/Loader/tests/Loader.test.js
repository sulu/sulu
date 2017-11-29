// @flow
import React from 'react';
import {render} from 'enzyme';
import Loader from '../Loader';

test('Render loader', () => {
    expect(render(<Loader />)).toMatchSnapshot();
});

test('Render loader with additional classname', () => {
    expect(render(<Loader className="test" />)).toMatchSnapshot();
});

test('Render loader with other dimensions', () => {
    expect(render(<Loader size={50} />)).toMatchSnapshot();
});
