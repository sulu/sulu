// @flow
import React from 'react';
import {fireEvent, render, screen} from '@testing-library/react';
import Chip from '../Chip';

test('Should render chip with children', () => {
    const {container} = render(<Chip value={{}}>Name</Chip>);
    expect(container).toMatchSnapshot();
});

test('Should render medium, primary chip with children', () => {
    const {container} = render(<Chip size="medium" skin="primary" value={{}}>Name</Chip>);
    expect(container).toMatchSnapshot();
});

test('Should render chip without delete icon in disabled state', () => {
    const {container} = render(<Chip disabled={true} onDelete={jest.fn()} value={{}}>Name</Chip>);
    expect(container).toMatchSnapshot();
});

test('Should render chip as clickable', () => {
    const {container} = render(<Chip onClick={jest.fn()} value={{}}>Name</Chip>);
    expect(container).toMatchSnapshot();
});

test('Should call onClick callback when the button is clicked', () => {
    const clickSpy = jest.fn();
    const value = {name: 'Test'};
    render(<Chip onClick={clickSpy} onDelete={jest.fn()} value={value}>Test</Chip>);

    fireEvent.click(screen.queryByText('Test'));

    expect(clickSpy).toBeCalledWith(value);
});

test('Should call onDelete callback when the times icon is clicked', () => {
    const deleteSpy = jest.fn();
    const value = {name: 'Test'};
    render(<Chip onDelete={deleteSpy} value={value}>Test</Chip>);

    fireEvent.click(screen.queryByLabelText('su-times'));

    expect(deleteSpy).toBeCalledWith(value);
});
