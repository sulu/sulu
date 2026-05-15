// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import SearchResult from '../SearchResult';

test('Render only with title', () => {
    render(
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

    expect(screen.getByText('Result')).toBeInTheDocument();
});

test('Render with all data', () => {
    render(
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

    expect(screen.getByText('Page')).toBeInTheDocument();
    expect(screen.getByText('(de)')).toBeInTheDocument();
    expect(screen.getByText('Description')).toBeInTheDocument();
    expect(screen.getByRole('img')).toHaveAttribute('src', '/image.jpg');
});

test('Render with icon instead of image', () => {
    render(
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

    expect(screen.getByLabelText('su-test')).toBeInTheDocument();
    expect(screen.queryByRole('img')).not.toBeInTheDocument();
});

test('Render with html description', () => {
    render(
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

    expect(screen.getByText('Description')).toBeInTheDocument();
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
    expect(clickSpy).toBeCalledWith(5);
});
