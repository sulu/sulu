// @flow
import React from 'react';
import {observable} from 'mobx';
import {mount, shallow} from 'enzyme';
import TextEditor from '../../TextEditor';
import Item from '../Item';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../TextEditor', () => jest.fn(({value}) => (<textarea value={value} />)));

test('Render Item with data', () => {
    const item = mount(
        <Item
            description="<p>Description</p>"
            editing={false}
            id={5}
            locale={observable.box('en')}
            onApply={jest.fn()}
            onCancel={jest.fn()}
            title="Title"
            type="page"
        />
    );

    expect(item.render()).toMatchSnapshot();
});

test('Render Item without data', () => {
    const item = mount(
        <Item
            description={undefined}
            editing={false}
            id={5}
            locale={observable.box('en')}
            onApply={jest.fn()}
            onCancel={jest.fn()}
            title={undefined}
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
            locale={observable.box('en')}
            onApply={jest.fn()}
            onCancel={jest.fn()}
            title="Title"
            type="page"
        />
    );

    expect(item.render()).toMatchSnapshot();
});

test('Pass correct props to text editor', () => {
    const item = mount(
        <Item
            description="Description"
            editing={true}
            id={5}
            locale={observable.box('en')}
            onApply={jest.fn()}
            onCancel={jest.fn()}
            title="Title"
            type="page"
        />
    );

    expect(item.find(TextEditor).prop('adapter')).toEqual('ckeditor5');
    expect(item.find(TextEditor).prop('locale').get()).toEqual('en');
});

test('Cancelling the item while editing should call the onClose callback', () => {
    const cancelSpy = jest.fn();

    const item = shallow(
        <Item
            description="Description"
            editing={true}
            id={5}
            locale={observable.box('en')}
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
            locale={observable.box('en')}
            onApply={jest.fn()}
            onCancel={jest.fn()}
            title="Edited title"
            type="page"
        />
    );

    item.find(TextEditor).prop('onChange')('Edited description');
    item.find('Input').prop('onChange')('Edited title');

    item.setProps({description: 'Description', editing: false, title: 'Title'});
    item.setProps({editing: true});

    expect(item.find(TextEditor).prop('value')).toEqual('Description');
    expect(item.find('Input').prop('value')).toEqual('Title');
});

test('Reset the current field when the title or description props change', () => {
    const item = shallow(
        <Item
            description="Edited description"
            editing={true}
            id={5}
            locale={observable.box('en')}
            onApply={jest.fn()}
            onCancel={jest.fn()}
            title="Edited title"
            type="page"
        />
    );

    item.find(TextEditor).prop('onChange')('Edited description');
    item.find('Input').prop('onChange')('Edited title');

    item.setProps({description: 'Description', title: 'Title'});

    expect(item.find(TextEditor).prop('value')).toEqual('Description');
    expect(item.find('Input').prop('value')).toEqual('Title');
});

test('Applying the item while editing should call the onApply callback with the current data', () => {
    const applySpy = jest.fn();

    const item = shallow(
        <Item
            description="Description"
            editing={true}
            id={5}
            locale={observable.box('en')}
            onApply={applySpy}
            onCancel={jest.fn()}
            title="Title"
            type="page"
        />
    );

    item.find(TextEditor).prop('onChange')('Edited description');
    item.find('Input').prop('onChange')('Edited title');

    expect(applySpy).not.toBeCalled();
    item.find('Button[children="sulu_admin.apply"]').simulate('click');
    expect(applySpy).toBeCalledWith({description: 'Edited description', id: 5, title: 'Edited title', type: 'page'});
});
