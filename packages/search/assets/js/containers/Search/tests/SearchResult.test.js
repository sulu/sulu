// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import SearchResult from '../SearchResult';

const defaultProps = ({
    description: undefined,
    icon: undefined,
    image: undefined,
    index: 1,
    locale: undefined,
    onClick: jest.fn(),
    resource: undefined,
    title: 'Result',
}: any);

test('Render only with title', () => {
    render(<SearchResult {...defaultProps} index={2} />);

    expect(screen.queryByRole('img')).not.toBeInTheDocument();
    expect(screen.getByText('Result')).toBeInTheDocument();
});

test('Render with all data', () => {
    render(
        <SearchResult
            {...defaultProps}
            description="Description"
            image="/image.jpg"
            index={5}
            locale="de"
            resource="Page"
        />
    );

    expect(screen.getByRole('img')).toHaveAttribute('src', '/image.jpg');
    expect(screen.getByText('Page')).toBeInTheDocument();
    expect(screen.getByText('(de)')).toBeInTheDocument();
    expect(screen.getByText('Description')).toBeInTheDocument();
});

test('Render with icon instead of image', () => {
    render(
        <SearchResult
            {...defaultProps}
            description="Description"
            icon="su-test"
            index={5}
            locale="de"
            resource="Page"
        />
    );

    expect(screen.getByLabelText('su-test')).toBeInTheDocument();
});

test('Render with html description', () => {
    render(
        <SearchResult
            {...defaultProps}
            description="<p>Description</p>"
            image="/image.jpg"
            index={5}
            locale="de"
            resource="Page"
        />
    );

    expect(screen.getByText('Description')).toBeInTheDocument();
});

test('Call callback with index when result is clicked', async() => {
    const user = userEvent.setup();
    const clickSpy = jest.fn();
    render(
        <SearchResult
            {...defaultProps}
            description="Description"
            image="/image.jpg"
            index={5}
            locale="de"
            onClick={clickSpy}
            resource="Page"
        />
    );

    await user.click(screen.getByRole('button'));

    expect(clickSpy).toBeCalledWith(5);
});
