// @flow
import React from 'react';
import {render} from 'enzyme';
import SearchResult from '../SearchResult';

test('Render only with title', () => {
    expect(render(
        <SearchResult
            description={undefined}
            image={undefined}
            locale={undefined}
            resource={undefined}
            title="Result"
        />
    )).toMatchSnapshot();
});

test('Render with all data', () => {
    expect(render(
        <SearchResult
            description="Description"
            image="/image.jpg"
            locale="de"
            resource="Page"
            title="Result"
        />
    )).toMatchSnapshot();
});
