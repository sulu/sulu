/* eslint-disable flowtype/require-valid-file-annotation */
import {render} from 'enzyme';
import React from 'react';
import Masonry from '../Masonry';

test('Render an empty Masonry container', () => {
    expect(render(<Masonry />)).toMatchSnapshot();
});

test('Render a Masonry container with items', () => {
    expect(render(
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
    )).toMatchSnapshot();
});
