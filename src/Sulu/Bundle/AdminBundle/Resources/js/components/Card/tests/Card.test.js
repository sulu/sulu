// @flow
import React from 'react';
import {shallow, render} from 'enzyme';
import Card from '../Card';

test('Render a card with its children', () => {
    expect(render(<Card><h1>Content!</h1></Card>)).toMatchSnapshot();
});

test('Render a card with its children, edit and remove icon', () => {
    expect(render(<Card onEdit={jest.fn()} onRemove={jest.fn()}>Content</Card>)).toMatchSnapshot();
});

test('Call onEdit callback when edit icon is clicked', () => {
    const editSpy = jest.fn();
    const card = shallow(<Card id={6} onEdit={editSpy}>Content</Card>);

    card.find('Icon[name="su-pen"]').simulate('click');

    expect(editSpy).toBeCalledWith(6);
});

test('Call onRemove callback when remove icon is clicked', () => {
    const removeSpy = jest.fn();
    const card = shallow(<Card id={2} onRemove={removeSpy}>Content</Card>);

    card.find('Icon[name="su-trash-alt"]').simulate('click');

    expect(removeSpy).toBeCalledWith(2);
});
