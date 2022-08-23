// @flow
import {render, screen} from '@testing-library/react';
import React from 'react';
import ProgressBar from '../ProgressBar';

test('The component should render', () => {
    const {container} = render(
        <ProgressBar
            max={10}
            value={7}
        />
    );

    expect(container).toMatchSnapshot();
});

test('The component should render with progress skin as default', () => {
    render(
        <ProgressBar
            max={10}
            value={5}
        />
    );

    const progress = screen.queryByRole('progressbar');
    expect(progress).toHaveAttribute('max', '10');
    expect(progress).toHaveValue(5);
    expect(progress).toHaveClass('progress');
    expect(progress).toHaveTextContent('50%');
});

test('The component should render with success skin', () => {
    render(
        <ProgressBar
            max={10}
            skin="success"
            value={10}
        />
    );

    const progress = screen.queryByRole('progressbar');
    expect(progress).toHaveAttribute('max', '10');
    expect(progress).toHaveValue(10);
    expect(progress).toHaveClass('success');
    expect(progress).toHaveTextContent('100%');
});

test('The component should render with warning skin', () => {
    render(
        <ProgressBar
            max={10}
            skin="warning"
            value={0}
        />
    );

    const progress = screen.queryByRole('progressbar');
    expect(progress).toHaveAttribute('max', '10');
    expect(progress).toHaveValue(0);
    expect(progress).toHaveClass('warning');
    expect(progress).toHaveTextContent('0%');
});

test('The component should render with error skin', () => {
    render(
        <ProgressBar
            max={10}
            skin="error"
            value={3}
        />
    );

    const progress = screen.queryByRole('progressbar');
    expect(progress).toHaveAttribute('max', '10');
    expect(progress).toHaveValue(3);
    expect(progress).toHaveClass('error');
    expect(progress).toHaveTextContent('30%');
});

test('The component should render with max 0', () => {
    render(
        <ProgressBar
            max={0}
            skin="error"
            value={0}
        />
    );

    const progress = screen.queryByRole('progressbar');
    expect(progress).toHaveAttribute('max', '1');
    expect(progress).toHaveValue(0);
    expect(progress).toHaveClass('error');
    expect(progress).toHaveTextContent('0%');
});
