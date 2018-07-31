// @flow
import React from 'react';
import {render} from 'enzyme';
import SearchResult from '../SearchResult';

test('Should render a SearchResult with title, url and description', () => {
    expect(render(<SearchResult title="Test SEO Title" url="http://www.sulu.io/test" description="Yay!" />))
        .toMatchSnapshot();
});

test('Should render a SearchResult without title, url and description', () => {
    expect(render(<SearchResult title={undefined} url={undefined} description={undefined} />))
        .toMatchSnapshot();
});
