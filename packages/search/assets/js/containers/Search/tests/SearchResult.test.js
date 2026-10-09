// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import SearchResult from '../SearchResult';

test('Render only with title', () => {
    const {asFragment} = render(
        <SearchResult
            description={undefined}
            icon={undefined}
            image={undefined}
            index={2}
            locale={undefined}
            onClick={jest.fn()}
            resource={undefined}
            title="Result"
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render with all data', () => {
    const {asFragment} = render(
        <SearchResult
            description="Description"
            icon={undefined}
            image="/image.jpg"
            index={5}
            locale="de"
            onClick={jest.fn()}
            resource="Page"
            title="Result"
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render with icon instead of image', () => {
    const {asFragment} = render(
        <SearchResult
            description="Description"
            icon="su-test"
            image={undefined}
            index={5}
            locale="de"
            onClick={jest.fn()}
            resource="Page"
            title="Result"
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render with html description', () => {
    const {asFragment} = render(
        <SearchResult
            description="<p>Description</p>"
            icon={undefined}
            image="/image.jpg"
            index={5}
            locale="de"
            onClick={jest.fn()}
            resource="Page"
            title="Result"
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Call callback with index when result is clicked', async() => {
    const user = userEvent.setup();
    const clickSpy = jest.fn();

    render(
        <SearchResult
            description="Description"
            icon={undefined}
            image="/image.jpg"
            index={5}
            locale="de"
            onClick={clickSpy}
            resource="Page"
            title="Result"
        />
    );

    await user.click(screen.getByRole('button'));

    expect(clickSpy).toHaveBeenCalledWith(5);
});
