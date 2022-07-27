// @flow
import React from 'react';
import {fireEvent, render, screen} from '@testing-library/react';
import Card from '../Card';

test('Render a card with its children', () => {
    const {container} = render(<Card><h1>Content!</h1></Card>);
    expect(container).toMatchSnapshot();
});

test('Render a card with its children, edit and remove icon', () => {
    const {container} = render(<Card onEdit={jest.fn()} onRemove={jest.fn()}>Content</Card>);
    expect(container).toMatchSnapshot();
});

test('Call onEdit callback when edit icon is clicked', () => {
    const editSpy = jest.fn();
    render(<Card id={6} onEdit={editSpy}>Content</Card>);
    const icon = screen.queryByLabelText('su-pen');

    fireEvent.click(icon);

    expect(editSpy).toBeCalledWith(6);
});

test('Call onRemove callback when remove icon is clicked', () => {
    const removeSpy = jest.fn();
    render(<Card id={2} onRemove={removeSpy}>Content</Card>);
    const icon = screen.queryByLabelText('su-trash-alt');

    fireEvent.click(icon);

    expect(removeSpy).toBeCalledWith(2);
});
