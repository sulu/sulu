/* eslint-disable flowtype/require-valid-file-annotation */
import {render} from 'enzyme';
import React from 'react';
import Divider from '../Divider';

test('Render an empty Divider', () => {
    const divider = render(<Divider />);
    expect(divider).toMatchSnapshot();
});

test('Render a Divider with text', () => {
    const divider = render(<Divider>Test</Divider>);
    expect(divider).toMatchSnapshot();
});
