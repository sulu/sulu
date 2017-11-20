// @flow
import React from 'react';
import {render} from 'enzyme';
import MediaDetail from '../MediaDetail';

test('Render a MediaDetail view', () => {
    expect(render(
        <MediaDetail store={{}} />
    )).toMatchSnapshot();
});
