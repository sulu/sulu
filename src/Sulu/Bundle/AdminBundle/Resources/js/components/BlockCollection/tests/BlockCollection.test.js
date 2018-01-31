// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import BlockCollection from '../BlockCollection';
import SortableContainer from '../SortableBlockList';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn().mockImplementation((key) => {
        switch (key) {
            case 'sulu_admin.add_block':
                return 'Add block';
        }
    }),
}));

test('Should render an empty block list', () => {
    expect(render(<BlockCollection onChange={jest.fn()} renderBlockContent={jest.fn()} />)).toMatchSnapshot();
});

test('Should render a filled block list', () => {
    const renderBlockContent = jest.fn();

    expect(render(
        <BlockCollection
            onChange={jest.fn()}
            renderBlockContent={renderBlockContent}
            value={[{content: 'Test 1'}, {content: 'Test 2'}]}
        />
    )).toMatchSnapshot();
});

test('Should allow to expand blocks', () => {
    const blockCollection = mount(
        <BlockCollection
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            value={[{content: 'Test 1'}, {content: 'Test 2'}]}
        />
    );
    const blocks = blockCollection.find('Block');

    expect(blocks.at(0).prop('expanded')).toEqual(false);
    expect(blocks.at(1).prop('expanded')).toEqual(false);

    blocks.at(1).simulate('click');

    expect(blocks.at(0).prop('expanded')).toEqual(false);
    expect(blocks.at(1).prop('expanded')).toEqual(true);
});

test('Should allow to collapse blocks', () => {
    const blockCollection = mount(
        <BlockCollection
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            value={[{content: 'Test 1'}, {content: 'Test 2'}]}
        />
    );
    const blocks = blockCollection.find('Block');

    blocks.at(0).simulate('click');
    blocks.at(1).simulate('click');

    expect(blocks.at(0).prop('expanded')).toEqual(true);
    expect(blocks.at(1).prop('expanded')).toEqual(true);

    blocks.at(0).find('.fa-times').simulate('click');

    expect(blocks.at(0).prop('expanded')).toEqual(false);
    expect(blocks.at(1).prop('expanded')).toEqual(true);
});

test('Should allow to reorder blocks by using drag and drop', () => {
    const changeSpy = jest.fn();
    const value = [{content: 'Test 1'}, {content: 'Test 2'}, {content: 'Test 3'}];
    const blockCollection = mount(
        <BlockCollection onChange={changeSpy} renderBlockContent={jest.fn()} value={value} />
    );

    blockCollection.find('Block').at(0).simulate('click');

    expect(blockCollection.get(0).expandedBlocks.toJS()).toEqual([true, false, false]);

    blockCollection.find(SortableContainer).prop('onSortEnd')({newIndex: 2, oldIndex: 0});
    expect(changeSpy).toBeCalledWith([{content: 'Test 2'}, {content: 'Test 3'}, {content: 'Test 1'}]);

    expect(blockCollection.get(0).expandedBlocks.toJS()).toEqual([false, false, true]);
});

test('Should allow to add a new block', () => {
    const changeSpy = jest.fn();
    const value = [{content: 'Test 1'}, {content: 'Test 2'}];
    const blockCollection = shallow(
        <BlockCollection onChange={changeSpy} renderBlockContent={jest.fn()} value={value} />
    );

    blockCollection.find('Button').simulate('click');

    expect(changeSpy).toBeCalledWith([...value, {}]);
});

test('Should allow to remove an existing block', () => {
    const value = [{content: 'Test 1'}, {content: 'Test 2'}];
    const changeSpy = jest.fn().mockImplementation((newValue) => {
        value.splice(0, value.length);
        value.push(...newValue);
    });
    const blockCollection = mount(
        <BlockCollection onChange={changeSpy} renderBlockContent={jest.fn()} value={value} />
    );

    const block1 = blockCollection.find('Block').at(0);
    block1.simulate('click');
    block1.find('.fa-trash-o').simulate('click');

    expect(changeSpy).toBeCalledWith([{content: 'Test 2'}]);
});

test('Should apply renderBlockContent before rendering the block content', () => {
    const prefix = 'This is the test for ';
    const value = [{content: 'Test 1'}, {content: 'Test 2'}];
    const renderBlockContent = jest.fn().mockImplementation((value) => prefix + value.content);
    const blockCollection = mount(
        <BlockCollection
            onChange={jest.fn()}
            renderBlockContent={renderBlockContent}
            value={value}
        />
    );
    const blocks = blockCollection.find('Block');

    blocks.at(0).simulate('click');
    blocks.at(1).simulate('click');

    expect(blocks.at(0).prop('children')).toEqual(prefix + value[0].content);
    expect(blocks.at(1).prop('children')).toEqual(prefix + value[1].content);
});
