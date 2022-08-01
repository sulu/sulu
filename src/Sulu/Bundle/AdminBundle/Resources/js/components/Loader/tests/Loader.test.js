// @flow
import React from 'react';
import {render} from '@testing-library/react';
import Loader from '../Loader';

test('Render loader', () => {
    const {container} = render(<Loader />);
    expect(container).toMatchSnapshot();
});

test('Render loader with additional classname', () => {
    const {container} = render(<Loader className="test" />);
    expect(container).toMatchSnapshot();
});

test('Render loader with other dimensions', () => {
    const {container} = render(<Loader size={50} />);
    expect(container).toMatchSnapshot();
});
