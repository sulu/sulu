// @flow
import React from 'react';
import {mount, shallow} from 'enzyme';
import Item from '../Item';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Render Item with data', () => {
    const item = mount(
        <Item
            description="Description"
            editing={false}
            id={5}
            onApply={jest.fn()}
            onCancel={jest.fn()}
            title="Title"
            type="page"
        />
    );

    expect(item.render()).toMatchSnapshot();
});

test('Render Item with data as form', () => {
    const item = mount(
        <Item
            description="Description"
            editing={true}
            id={5}
            onApply={jest.fn()}
            onCancel={jest.fn()}
            title="Title"
            type="page"
        />
    );

    expect(item.render()).toMatchSnapshot();
});

test('Cancelling the item while editing should call the onClose callback', () => {
    const cancelSpy = jest.fn();

    const item = shallow(
        <Item
            description="Description"
            editing={true}
            id={5}
            onApply={jest.fn()}
            onCancel={cancelSpy}
            title="Title"
            type="page"
        />
    );

    expect(cancelSpy).not.toBeCalled();
    item.find('Button[children="sulu_admin.cancel"]').simulate('click');
    expect(cancelSpy).toBeCalledWith(5);
});

test('Reset the current field when the edit form is closed', () => {
    const item = shallow(
        <Item
            description="Edited description"
            editing={true}
            id={5}
            onApply={jest.fn()}
            onCancel={jest.fn()}
            title="Edited title"
            type="page"
        />
    );

    item.find('TextArea').prop('onChange')('Edited description');
    item.find('Input').prop('onChange')('Edited title');

    item.setProps({description: 'Description', editing: false, title: 'Title'});
    item.setProps({editing: true});

    expect(item.find('TextArea').prop('value')).toEqual('Description');
    expect(item.find('Input').prop('value')).toEqual('Title');
});

test('Applying the item while editing should call the onApply callback with the current data', () => {
    const applySpy = jest.fn();

    const item = shallow(
        <Item
            description="Description"
            editing={true}
            id={5}
            onApply={applySpy}
            onCancel={jest.fn()}
            title="Title"
            type="page"
        />
    );

    item.find('TextArea').prop('onChange')('Edited description');
    item.find('Input').prop('onChange')('Edited title');

    expect(applySpy).not.toBeCalled();
    item.find('Button[children="sulu_admin.apply"]').simulate('click');
    expect(applySpy).toBeCalledWith({description: 'Edited description', id: 5, title: 'Edited title', type: 'page'});
});
