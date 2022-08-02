// @flow
import React from 'react';
import {render} from '@testing-library/react';
import Heading from '../Heading';

test('Render heading', () => {
    const {container} = render(
        <Heading description="Hides a block when activated" icon="su-hide" label="Hide a block">
            Hello World!
        </Heading>
    );
    expect(container).toMatchSnapshot();
});
