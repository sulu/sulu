// @flow
import {render} from 'enzyme';
import React from 'react';
import Sticky from '../Sticky.js';

test('The component should render', () => {
    const component = render(
        <Sticky>{
            (isSticky) => <span>{isSticky ? 'Stick' : 'Unsticky'}</span>
        }</Sticky>
    );

    expect(component).toMatchSnapshot();
});
