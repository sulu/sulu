// @flow
import {render} from 'enzyme';
import React from 'react';
import Divider from '../Divider';

test('The component should render', () => {
    const view = render(<Divider />);
    expect(view).toMatchSnapshot();
});
