// @flow
import React from 'react';
import {render} from 'enzyme';
import Heading from '../Heading';

test('Render heading', () => {
    expect(render(
        <Heading description="Hides a block when activated" icon="su-hide" label="Hide a block">
            Hello World!
        </Heading>
    )).toMatchSnapshot();
});
