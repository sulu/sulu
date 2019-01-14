// @flow
import React from 'react';
import {render} from 'enzyme';
import SearchResult from '../SearchResult';

test('Should render a SearchResult with title, url and description', () => {
    expect(render(<SearchResult description="Yay!" title="Test SEO Title" url="http://www.sulu.io/test" />))
        .toMatchSnapshot();
});

test('Should render a SearchResult without title, url and description', () => {
    expect(render(<SearchResult description={undefined} title={undefined} url={undefined} />))
        .toMatchSnapshot();
});
