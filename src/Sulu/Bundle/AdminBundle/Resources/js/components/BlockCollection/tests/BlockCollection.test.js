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

test('Choosing a different type should change the type', () => {
    const renderBlockContent = jest.fn();
    const blockCollection = mount(
        <BlockCollection
            onChange={jest.fn()}
            renderBlockContent={renderBlockContent}
            types={{type1: 'Type 1', type2: 'Type2'}}
            value={[{content: 'Test 1'}, {content: 'Test 2'}]}
        />
    );

    const block1 = blockCollection.find('Block').at(0);
    const block2 = blockCollection.find('Block').at(1);

    block1.simulate('click');
    block2.simulate('click');

    const select1 = block1.find('SingleSelect');
    const select2 = block2.find('SingleSelect');
    expect(select1.prop('value')).toEqual('type1');
    expect(select2.prop('value')).toEqual('type1');

    select1.prop('onChange')('type2');
    expect(select1.prop('value')).toEqual('type2');
    expect(select2.prop('value')).toEqual('type1');
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

test('Should keep types when reordering blocks by using drag and drop', () => {
    const changeSpy = jest.fn();
    const value = [{content: 'Test 1'}, {content: 'Test 2'}, {content: 'Test 3'}];
    const blockCollection = mount(
        <BlockCollection
            onChange={changeSpy}
            renderBlockContent={jest.fn()}
            types={{type1: 'Type 1', type2: 'Type 2'}}
            value={value}
        />
    );

    expect(blockCollection.get(0).blockTypes.toJS()).toEqual(['type1', 'type1', 'type1']);

    const block1 = blockCollection.find('Block').at(0);
    block1.simulate('click');
    block1.find('SingleSelect').prop('onChange')('type2');
    expect(blockCollection.get(0).blockTypes.toJS()).toEqual(['type2', 'type1', 'type1']);

    blockCollection.find(SortableContainer).prop('onSortEnd')({newIndex: 2, oldIndex: 0});

    expect(blockCollection.get(0).blockTypes.toJS()).toEqual(['type1', 'type1', 'type2']);
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

test('Should apply renderBlockContent before rendering the block content including the type', () => {
    const prefix = 'This is the test for ';
    const typePrefix = ' which has a type of ';
    const value = [{content: 'Test 1'}, {content: 'Test 2'}];
    const renderBlockContent = jest.fn().mockImplementation(
        (value, type) => prefix + value.content + (type ? typePrefix + type : '')
    );
    const types = {
        type1: 'Type 1',
        type2: 'Type 2',
    };

    const blockCollection = mount(
        <BlockCollection
            onChange={jest.fn()}
            renderBlockContent={renderBlockContent}
            types={types}
            value={value}
        />
    );
    const blocks = blockCollection.find('Block');

    blocks.at(0).simulate('click');
    blocks.at(1).simulate('click');

    expect(blocks.at(0).prop('children')).toEqual(prefix + value[0].content + typePrefix + 'type1');
    expect(blocks.at(1).prop('children')).toEqual(prefix + value[1].content + typePrefix + 'type1');

    expect(blocks.at(0).prop('children')).toEqual(prefix + value[0].content + typePrefix + 'type1');
    expect(blocks.at(1).prop('children')).toEqual(prefix + value[1].content + typePrefix + 'type1');

    blocks.at(1).find('SingleSelect').prop('onChange')('type2');

    expect(blocks.at(0).prop('children')).toEqual(prefix + value[0].content + typePrefix + 'type1');
    expect(blocks.at(1).prop('children')).toEqual(prefix + value[1].content + typePrefix + 'type2');
});
