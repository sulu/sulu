// @flow
import {render} from '@testing-library/react';
import React from 'react';
import Divider from '../Divider';

test('Render an empty Divider', () => {
    const {container} = render(<Divider />);
    expect(container).toMatchSnapshot();
});

test('Render a Divider with text', () => {
    const {container} = render(<Divider>Test</Divider>);
    expect(container).toMatchSnapshot();
});
