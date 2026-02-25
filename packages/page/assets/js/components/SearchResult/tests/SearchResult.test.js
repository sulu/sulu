// @flow
import React from 'react';
import {render} from '@testing-library/react';
import SearchResult from '../SearchResult';

test('Should render a SearchResult with title, url and description', () => {
    const {asFragment} = render(
        <SearchResult description="Yay!" title="Test SEO Title" url="http://www.sulu.io/test" />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Should render a SearchResult without title, url and description', () => {
    const {asFragment} = render(
        <SearchResult description={undefined} title={undefined} url={undefined} />
    );

    expect(asFragment()).toMatchSnapshot();
});
