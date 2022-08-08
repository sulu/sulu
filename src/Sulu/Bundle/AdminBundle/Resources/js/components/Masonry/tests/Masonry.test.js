// @flow
import {render} from '@testing-library/react';
import React from 'react';
import Masonry from '../Masonry';

test('Render an empty Masonry container', () => {
    const {container} = render(<Masonry />);
    expect(container).toMatchSnapshot();
});

test('Render a Masonry container with items', () => {
    const {container} = render(
        <Masonry>
            <div>
                <img src="http://lorempixel.com/300/200" />
            </div>
            <div>
                <img src="http://lorempixel.com/300/200" />
            </div>
            <div>
                <img src="http://lorempixel.com/300/200" />
            </div>
        </Masonry>
    );
    expect(container).toMatchSnapshot();
});
