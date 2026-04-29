// @flow
import {render} from '@testing-library/react';
import React from 'react';
import Sticky from '../Sticky.js';

test('The component should render', () => {
    const {asFragment} = render(
        <Sticky>{
            (isSticky) => <span>{isSticky ? 'Stick' : 'Unsticky'}</span>
        }</Sticky>
    );

    expect(asFragment()).toMatchSnapshot();
});
