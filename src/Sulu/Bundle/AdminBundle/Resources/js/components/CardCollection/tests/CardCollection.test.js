// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import CardCollection from '../CardCollection';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Render empty CardCollection', () => {
    expect(render(<CardCollection />)).toMatchSnapshot();
});

test('Render passed card components', () => {
    expect(render(
        <CardCollection>
            <CardCollection.Card>
                <h1>Content 1</h1>
            </CardCollection.Card>
            <CardCollection.Card>
                <h2>Content 2</h2>
            </CardCollection.Card>
        </CardCollection>
    )).toMatchSnapshot();
});

test('Call onAdd callback when add button is clicked', () => {
    const addSpy = jest.fn();

    const cardCollection = shallow(<CardCollection onAdd={addSpy} />);

    cardCollection.find('Button[icon="su-plus"]').simulate('click');

    expect(addSpy).toBeCalledWith();
});

test('Call onEdit callback when edit icon is clicked', () => {
    const editSpy = jest.fn();

    const cardCollection = shallow(
        <CardCollection onEdit={editSpy}>
            <CardCollection.Card>
                <h1>Content 1</h1>
            </CardCollection.Card>
            <CardCollection.Card>
                <h2>Content 2</h2>
            </CardCollection.Card>
        </CardCollection>
    );

    cardCollection.find('Card').at(1).simulate('edit', 1);

    expect(editSpy).toBeCalledWith(1);
});

test('Call onRemove callback when remove icon is clicked', () => {
    const removeSpy = jest.fn();

    const cardCollection = shallow(
        <CardCollection onRemove={removeSpy}>
            <CardCollection.Card>
                <h1>Content 1</h1>
            </CardCollection.Card>
            <CardCollection.Card>
                <h2>Content 2</h2>
            </CardCollection.Card>
        </CardCollection>
    );

    cardCollection.find('Card').at(1).simulate('remove', 1);

    expect(removeSpy).toBeCalledWith(1);
});
