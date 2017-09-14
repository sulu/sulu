/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render} from 'enzyme';
import Loader from '../Loader';

test('Render loader', () => {
    expect(render(<Loader />)).toMatchSnapshot();
});
