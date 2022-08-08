// @flow
import {render} from '@testing-library/react';
import React from 'react';
import Divider from '../Divider';

test('The component should render', () => {
    const {container} = render(<Divider />);
    expect(container).toMatchSnapshot();
});
