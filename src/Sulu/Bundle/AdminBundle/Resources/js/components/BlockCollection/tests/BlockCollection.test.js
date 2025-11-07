// @flow
import React from 'react';
import {observable} from 'mobx';
import {mount, render, shallow} from 'enzyme';
import BlockCollection from '../BlockCollection';
import SortableBlockList from '../SortableBlockList';
import clipboard from '../../../utils/clipboard/clipboard';

beforeEach(() => {
    BlockCollection.idCounter = 0;
});

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Should render a fully filled block list', () => {
    expect(render(
        <BlockCollection
            defaultType="editor"
            icons={[[], ['su-eye']]}
            maxOccurs={3}
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            value={[{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}]}
        />
    )).toMatchSnapshot();
});

test('Should render a non-movable block list', () => {
    expect(render(
        <BlockCollection
            collapsable={false}
            defaultType="editor"
            movable={false}
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            value={[{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}]}
        />
    )).toMatchSnapshot();
});

test('Should render a disabled block list', () => {
    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            disabled={true}
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            value={[{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}]}
        />
    );

    expect(blockCollection.find('.sortableBlockList.disabled')).toHaveLength(1);
    expect(blockCollection.find('Button[icon="su-plus"]').last().prop('disabled')).toEqual(true);
});

test('Should mark the add button disabled if maxOccurs is reached', () => {
    const blockCollection = shallow(
        <BlockCollection
            defaultType="editor"
            maxOccurs={2}
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            value={[{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}]}
        />
    );

    expect(blockCollection.find('Button[icon="su-plus"]').prop('disabled')).toEqual(true);
});

test('Should render add button with the given addButtonText', () => {
    const blockCollection = shallow(
        <BlockCollection
            addButtonText="custom-add-button-text"
            defaultType="editor"
            maxOccurs={2}
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            value={[{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}]}
        />
    );

    expect(blockCollection.find('Button[icon="su-plus"]').prop('children')).toEqual('custom-add-button-text');
});

test('Should render paste button if clipboard contains a block', () => {
    const blockCollection = shallow(
        <BlockCollection
            defaultType="editor"
            maxOccurs={2}
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            value={[{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}]}
        />
    );

    expect(blockCollection.find('Button[icon="su-copy"]').exists()).toBeFalsy();

    clipboard.set('blocks', [{content: 'Test 3', type: 'editor'}]);

    expect(blockCollection.find('Button[icon="su-copy"]').exists()).toBeTruthy();
    expect(blockCollection.find('Button[icon="su-copy"]').prop('children')).toEqual('sulu_admin.paste_blocks');
});

test('Should render paste button with the given pasteButtonText', () => {
    clipboard.set('blocks', [{content: 'Test 3', type: 'editor'}]);

    const blockCollection = shallow(
        <BlockCollection
            defaultType="editor"
            maxOccurs={2}
            onChange={jest.fn()}
            pasteButtonText="custom-paste-button-text"
            renderBlockContent={jest.fn()}
            value={[{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}]}
        />
    );

    expect(blockCollection.find('Button[icon="su-copy"]').exists()).toBeTruthy();
    expect(blockCollection.find('Button[icon="su-copy"]').prop('children')).toEqual('custom-paste-button-text');
});

test('Should add at least the minOccurs amount of blocks', () => {
    const changeSpy = jest.fn();
    const value = [{type: 'editor'}];

    shallow(
        <BlockCollection
            defaultType="editor"
            minOccurs={2}
            onChange={changeSpy}
            renderBlockContent={jest.fn()}
            value={value}
        />
    );

    expect(changeSpy).toBeCalledWith([
        expect.objectContaining({}),
        expect.objectContaining({}),
    ]);
});

test('Should fill the array up to minOccurs with different objects', () => {
    const changeSpy = jest.fn();
    const value = [];

    shallow(
        <BlockCollection
            defaultType="editor"
            minOccurs={2}
            onChange={changeSpy}
            renderBlockContent={jest.fn()}
            value={value}
        />
    );

    expect(changeSpy).toBeCalledWith([
        expect.objectContaining({}),
        expect.objectContaining({}),
    ]);
    const changeSpyCall = changeSpy.mock.calls[0][0];
    expect(changeSpyCall[0]).not.toBe(changeSpyCall[1]);
});

test('Should add at least the minOccurs amount of blocks with empty starting value', () => {
    const changeSpy = jest.fn();
    const value = [];

    shallow(
        <BlockCollection
            defaultType="editor"
            minOccurs={2}
            onChange={changeSpy}
            renderBlockContent={jest.fn()}
            value={value}
        />
    );

    expect(changeSpy).toBeCalledWith([
        expect.objectContaining({}),
        expect.objectContaining({}),
    ]);
});

test('Should add at least the minOccurs amount of blocks with types', () => {
    const changeSpy = jest.fn();
    const value = [{type: 'default'}];

    shallow(
        <BlockCollection
            defaultType="editor"
            minOccurs={2}
            onChange={changeSpy}
            renderBlockContent={jest.fn()}
            types={{default: 'Default', editor: 'Editor'}}
            value={value}
        />
    );

    expect(changeSpy).toBeCalledWith([
        expect.objectContaining({type: 'default'}),
        expect.objectContaining({type: 'editor'}),
    ]);
});

test('Choosing a different type should call the onChange callback', () => {
    const changeSpy = jest.fn();
    const renderBlockContent = jest.fn();
    const value = [
        {
            type: 'type1',
            content: 'Test 1',
        },
        {
            type: 'type2',
            content: 'Test 2',
        },
    ];
    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={changeSpy}
            renderBlockContent={renderBlockContent}
            types={{type1: 'Type 1', type2: 'Type2'}}
            value={value}
        />
    );

    blockCollection.find('Block').at(0).simulate('click');
    blockCollection.find('Block').at(1).simulate('click');

    expect(blockCollection.find('Block').at(0).find('SingleSelect').prop('value')).toEqual('type1');
    expect(blockCollection.find('Block').at(1).find('SingleSelect').prop('value')).toEqual('type2');

    blockCollection.find('Block').at(0).find('SingleSelect').prop('onChange')('type2');

    expect(changeSpy).toBeCalledWith([
        expect.objectContaining({content: 'Test 1', type: 'type2'}),
        expect.objectContaining({content: 'Test 2', type: 'type2'}),
    ]);
});

test('Should allow to expand blocks', () => {
    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            value={[{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}]}
        />
    );

    expect(blockCollection.find('Block').at(0).prop('expanded')).toEqual(false);
    expect(blockCollection.find('Block').at(1).prop('expanded')).toEqual(false);

    blockCollection.find('Block').at(1).simulate('click');

    expect(blockCollection.find('Block').at(0).prop('expanded')).toEqual(false);
    expect(blockCollection.find('Block').at(1).prop('expanded')).toEqual(true);
});

test('Should allow to collapse blocks', () => {
    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            value={[{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}]}
        />
    );

    blockCollection.find('Block').at(0).simulate('click');
    blockCollection.find('Block').at(1).simulate('click');

    expect(blockCollection.find('Block').at(0).prop('expanded')).toEqual(true);
    expect(blockCollection.find('Block').at(1).prop('expanded')).toEqual(true);

    blockCollection.find('Block').at(0).find('Icon[name="su-collapse-vertical"]').simulate('click');

    expect(blockCollection.find('Block').at(0).prop('expanded')).toEqual(false);
    expect(blockCollection.find('Block').at(1).prop('expanded')).toEqual(true);
});

test('Should allow to collapse all blocks', () => {
    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            value={[{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}]}
        />
    );

    blockCollection.find('Block').at(0).simulate('click');
    blockCollection.find('Block').at(1).simulate('click');

    expect(blockCollection.find('Block').at(0).prop('expanded')).toEqual(true);
    expect(blockCollection.find('Block').at(1).prop('expanded')).toEqual(true);

    blockCollection.find('button.blockCollectionActionButton').last().simulate('click');

    expect(blockCollection.find('Block').at(0).prop('expanded')).toEqual(false);
    expect(blockCollection.find('Block').at(1).prop('expanded')).toEqual(false);
});

test('Should allow to expand all blocks', () => {
    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            value={[{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}]}
        />
    );

    expect(blockCollection.find('Block').at(0).prop('expanded')).toEqual(false);
    expect(blockCollection.find('Block').at(1).prop('expanded')).toEqual(false);

    blockCollection.find('button.blockCollectionActionButton').last().simulate('click');

    expect(blockCollection.find('Block').at(0).prop('expanded')).toEqual(true);
    expect(blockCollection.find('Block').at(1).prop('expanded')).toEqual(true);
});

test('Should allow to reorder blocks by using drag and drop', () => {
    const changeSpy = jest.fn();
    const sortEndSpy = jest.fn();
    const value = [
        {content: 'Test 1', type: 'editor'},
        {content: 'Test 2', type: 'editor'},
        {content: 'Test 3', type: 'editor'},
    ];
    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={changeSpy}
            onSortEnd={sortEndSpy}
            renderBlockContent={jest.fn()}
            value={value}
        />
    );

    blockCollection.find('Block').at(0).simulate('click');

    expect(blockCollection.instance().expandedBlocks.toJS()).toEqual([true, false, false]);
    expect(blockCollection.instance().generatedBlockIds.toJS()).toEqual([1, 2, 3]);

    blockCollection.find(SortableBlockList).prop('onSortEnd')({newIndex: 2, oldIndex: 0});
    expect(changeSpy).toBeCalledWith([
        expect.objectContaining({content: 'Test 2'}),
        expect.objectContaining({content: 'Test 3'}),
        expect.objectContaining({content: 'Test 1'}),
    ]);
    expect(sortEndSpy).toBeCalledWith(0, 2);

    expect(blockCollection.instance().expandedBlocks.toJS()).toEqual([false, false, true]);
    expect(blockCollection.instance().generatedBlockIds.toJS()).toEqual([2, 3, 1]);
});

test('Should add a new block between existing blocks', () => {
    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];
    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={changeSpy}
            renderBlockContent={jest.fn()}
            value={value}
        />
    );

    blockCollection.find('Button[icon="su-plus"]').at(0).simulate('click');

    expect(changeSpy).toBeCalledWith([
        {content: 'Test 1', type: 'editor'},
        {type: 'editor'},
        {content: 'Test 2', type: 'editor'},
    ]);
});

test('Should add a new block at the end', () => {
    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];
    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={changeSpy}
            renderBlockContent={jest.fn()}
            value={value}
        />
    );

    blockCollection.find('Button[icon="su-plus"]').last().simulate('click');

    expect(changeSpy).toBeCalledWith([...value, {type: 'editor'}]);
});

test('Should throw an exception if a new block is added and the maximum has already been reached', () => {
    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];

    const blockCollection = shallow(
        <BlockCollection
            defaultType="editor"
            maxOccurs={2}
            onChange={changeSpy}
            renderBlockContent={jest.fn()}
            value={value}
        />
    );

    expect(() => blockCollection.instance().handleAddBlock()).toThrow(/maximum amount of blocks/);
});

test('Should paste block between existing blocks', () => {
    clipboard.set('blocks', [{content: 'Clipboard', type: 'editor'}]);

    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];
    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={changeSpy}
            renderBlockContent={jest.fn()}
            value={value}
        />
    );

    blockCollection.find('Button[icon="su-copy"]').at(0).simulate('click');

    expect(changeSpy).toBeCalledWith([
        {content: 'Test 1', type: 'editor'},
        {content: 'Clipboard', type: 'editor'},
        {content: 'Test 2', type: 'editor'},
    ]);
});

test('Should paste block at the end', () => {
    clipboard.set('blocks', [{content: 'Clipboard', type: 'editor'}]);

    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];
    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={changeSpy}
            renderBlockContent={jest.fn()}
            value={value}
        />
    );

    blockCollection.find('Button[icon="su-copy"]').last().simulate('click');

    expect(changeSpy).toBeCalledWith([...value, {content: 'Clipboard', type: 'editor'}]);
});

test('Should paste block with default type if type of block in clipboard block is not known', () => {
    clipboard.set('blocks', [{content: 'Clipboard', type: 'unkown-type'}]);

    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];
    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={changeSpy}
            renderBlockContent={jest.fn()}
            value={value}
        />
    );

    blockCollection.find('Button[icon="su-copy"]').last().simulate('click');

    expect(changeSpy).toBeCalledWith([...value, {content: 'Clipboard', type: 'editor'}]);
});

test('Should throw an exception if a block is pasted and the maximum has already been reached', () => {
    clipboard.set('blocks', [{content: 'Clipboard', type: 'editor'}]);

    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];

    const blockCollection = shallow(
        <BlockCollection
            defaultType="editor"
            maxOccurs={2}
            onChange={changeSpy}
            renderBlockContent={jest.fn()}
            value={value}
        />
    );

    expect(() => blockCollection.instance().handlePasteBlocks()).toThrow(/maximum amount of blocks/);
});

test('Should pass duplicate action that allows to duplicate an existing block', () => {
    // update value that is passed to the component when change callback is fired to prevent warnings
    const value: any = observable([{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}]);
    const changeSpy = jest.fn().mockImplementation((newValue) => {
        value.splice(0, value.length);
        value.push(...newValue);
    });
    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={changeSpy}
            renderBlockContent={jest.fn()}
            value={value}
        />
    );

    const blockActions = blockCollection.find('Block').at(0).prop('actions');
    expect(blockActions).toContainEqual(
        expect.objectContaining({
            type: 'button',
            icon: 'su-duplicate',
            label: 'sulu_admin.duplicate',
        })
    );

    blockCollection.find('Block').at(0).simulate('click');
    blockCollection.find('Block').at(0).find('Icon[name="su-more-circle"]').simulate('click');
    blockCollection.find('Block').at(0).find('Icon[name="su-duplicate"]').simulate('click');

    expect(changeSpy).toBeCalledWith([
        {content: 'Test 1', type: 'editor'},
        {content: 'Test 1', type: 'editor'},
        {content: 'Test 2', type: 'editor'},
    ]);
});

test('Should not pass duplicate action to Block component if maxOccurs limit is reached', () => {
    const value = [{content: 'Value 1', type: 'editor'}, {content: 'Value 2', type: 'editor'}];

    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            maxOccurs={2}
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            value={value}
        />
    );

    const blockActions = blockCollection.find('Block').at(0).prop('actions');
    expect(blockActions).not.toContainEqual(
        expect.objectContaining({
            label: 'sulu_admin.duplicate',
        })
    );
});

test('Should throw an exception if a block is duplicated and maxOccurs limit is reached', () => {
    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];

    const blockCollection = shallow(
        <BlockCollection
            defaultType="editor"
            maxOccurs={2}
            onChange={changeSpy}
            renderBlockContent={jest.fn()}
            value={value}
        />
    );

    expect(() => blockCollection.instance().handleDuplicateBlock(0)).toThrow(/maximum amount of blocks/);
});

test('Should pass remove action that allows to remove an existing block', () => {
    // update value that is passed to the component when change callback is fired to prevent warnings
    const value: any = observable([{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}]);
    const changeSpy = jest.fn().mockImplementation((newValue) => {
        value.splice(0, value.length);
        value.push(...newValue);
    });
    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={changeSpy}
            renderBlockContent={jest.fn()}
            value={value}
        />
    );

    const blockActions = blockCollection.find('Block').at(0).prop('actions');
    expect(blockActions).toContainEqual(
        expect.objectContaining({
            type: 'button',
            icon: 'su-trash-alt',
            label: 'sulu_admin.delete',
        })
    );

    blockCollection.find('Block').at(0).simulate('click');
    blockCollection.find('Block').at(0).find('Icon[name="su-more-circle"]').simulate('click');
    blockCollection.find('Block').at(0).find('Icon[name="su-trash-alt"]').simulate('click');

    expect(changeSpy).toBeCalledWith([expect.objectContaining({content: 'Test 2'})]);
});

test('Should not pass remove action to Block component if minOccurs limit is reached', () => {
    const value = [{content: 'Value 1', type: 'editor'}, {content: 'Value 2', type: 'editor'}];

    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            minOccurs={2}
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            value={value}
        />
    );

    const blockActions = blockCollection.find('Block').at(0).prop('actions');
    expect(blockActions).not.toContainEqual(expect.objectContaining({
        label: 'sulu_admin.delete',
    }));
});

test('Should throw an exception if a block is removed and minOccurs limit is reached', () => {
    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];

    const blockCollection = shallow(
        <BlockCollection
            defaultType="editor"
            minOccurs={2}
            onChange={changeSpy}
            renderBlockContent={jest.fn()}
            value={value}
        />
    );

    expect(() => blockCollection.instance().handleRemoveBlock(0)).toThrow(/minimum amount of blocks/);
});

test('Should pass copy action that allows to cut an existing block into the clipboard', () => {
    const clipboardSpy = jest.fn();
    clipboard.observe('blocks', clipboardSpy);

    const value: any = observable([{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}]);
    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            value={value}
        />
    );

    const blockActions = blockCollection.find('Block').at(0).prop('actions');
    expect(blockActions).toContainEqual(
        expect.objectContaining({
            type: 'button',
            icon: 'su-copy',
            label: 'sulu_admin.copy',
        })
    );

    expect(clipboardSpy).not.toBeCalled();

    blockCollection.find('Block').at(0).simulate('click');
    blockCollection.find('Block').at(0).find('Icon[name="su-more-circle"]').simulate('click');
    blockCollection.find('Block').at(0).find('Icon[name="su-copy"]').simulate('click');

    expect(clipboardSpy).toBeCalledWith([value[0]]);
});

test('Should pass cut action that allows to cut an existing block into the clipboard', () => {
    const clipboardSpy = jest.fn();
    clipboard.observe('blocks', clipboardSpy);

    // update value that is passed to the component when change callback is fired to prevent warnings
    const value: any = observable([{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}]);
    const changeSpy = jest.fn().mockImplementation((newValue) => {
        value.splice(0, value.length);
        value.push(...newValue);
    });
    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={changeSpy}
            renderBlockContent={jest.fn()}
            value={value}
        />
    );

    const blockActions = blockCollection.find('Block').at(0).prop('actions');
    expect(blockActions).toContainEqual(
        expect.objectContaining({
            type: 'button',
            icon: 'su-scissors',
            label: 'sulu_admin.cut',
        })
    );

    expect(clipboardSpy).not.toBeCalled();

    blockCollection.find('Block').at(0).simulate('click');
    blockCollection.find('Block').at(0).find('Icon[name="su-more-circle"]').simulate('click');
    blockCollection.find('Block').at(0).find('Icon[name="su-scissors"]').simulate('click');

    expect(clipboardSpy).toBeCalledWith([expect.objectContaining({content: 'Test 1'})]);
    expect(changeSpy).toBeCalledWith([expect.objectContaining({content: 'Test 2'})]);
});

test('Should not pass cut action to Block component if minOccurs limit is reached', () => {
    const value = [{content: 'Value 1', type: 'editor'}, {content: 'Value 2', type: 'editor'}];

    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            minOccurs={2}
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            value={value}
        />
    );

    const blockActions = blockCollection.find('Block').at(0).prop('actions');
    expect(blockActions).not.toContainEqual(expect.objectContaining({
        label: 'sulu_admin.cut',
    }));
});

test('Should throw an exception if a block is removed and minOccurs limit is reached', () => {
    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];

    const blockCollection = shallow(
        <BlockCollection
            defaultType="editor"
            minOccurs={2}
            onChange={changeSpy}
            renderBlockContent={jest.fn()}
            value={value}
        />
    );

    expect(() => blockCollection.instance().handleCutBlock(0)).toThrow(/minimum amount of blocks/);
});

test('Should call onSettingsClick callback when settings icon is clicked', () => {
    const settingsClickSpy = jest.fn();
    const value: any = observable([{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}]);
    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={jest.fn()}
            onSettingsClick={settingsClickSpy}
            renderBlockContent={jest.fn()}
            value={value}
        />
    );

    blockCollection.find('Block').at(0).simulate('click');
    blockCollection.find('Block').at(1).simulate('click');

    blockCollection.find('Block Icon[name="su-cog"]').at(0).simulate('click');
    expect(settingsClickSpy).toHaveBeenLastCalledWith(0);

    blockCollection.find('Block Icon[name="su-cog"]').at(1).simulate('click');
    expect(settingsClickSpy).toHaveBeenLastCalledWith(1);
});

test('Should apply renderBlockContent before rendering the block content', () => {
    const prefix = 'This is the test for ';
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];
    const renderBlockContent = jest.fn().mockImplementation((value) => prefix + value.content);
    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={jest.fn()}
            renderBlockContent={renderBlockContent}
            value={value}
        />
    );

    blockCollection.find('Block').at(0).simulate('click');
    blockCollection.find('Block').at(1).simulate('click');

    expect(blockCollection.find('Block').at(0).prop('children')).toEqual(prefix + value[0].content);
    expect(blockCollection.find('Block').at(1).prop('children')).toEqual(prefix + value[1].content);
});

test('Should apply renderBlockContent before rendering the block content including the type', () => {
    const prefix = 'This is the test for ';
    const typePrefix = ' which has a type of ';
    const value = [
        {
            type: 'type2',
            content: 'Test 1',
        },
        {
            type: 'type1',
            content: 'Test 2',
        },
    ];
    const renderBlockContent = jest.fn().mockImplementation(
        (value, type) => prefix + value.content + (type ? typePrefix + type : '')
    );
    const types = {
        type1: 'Type 1',
        type2: 'Type 2',
    };

    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={jest.fn()}
            renderBlockContent={renderBlockContent}
            types={types}
            value={value}
        />
    );

    blockCollection.find('Block').at(0).simulate('click');
    blockCollection.find('Block').at(1).simulate('click');

    expect(blockCollection.find('Block').at(0).prop('children'))
        .toEqual(prefix + value[0].content + typePrefix + 'type2');
    expect(blockCollection.find('Block').at(1).prop('children'))
        .toEqual(prefix + value[1].content + typePrefix + 'type1');
});

test('Should adjust expandedBlocks and generatedBlockIds after updating the value variable with fewer entries', () => {
    const types = {
        type1: 'Type 1',
        type2: 'Type 2',
    };

    const value = [
        {
            type: 'type1',
            content: 'Test 1',
        },
        {
            type: 'type2',
            content: 'Test 2',
        },
        {
            type: 'type1',
            content: 'Test 3',
        },
    ];

    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            types={types}
            value={value}
        />
    );

    blockCollection.instance().expandedBlocks[0] = true;

    expect(blockCollection.props().value.length).toBe(3);
    expect(blockCollection.instance().expandedBlocks.length).toBe(3);
    expect(blockCollection.instance().generatedBlockIds.length).toBe(3);
    expect(blockCollection.instance().expandedBlocks[0]).toBe(true);

    blockCollection.setProps({
        value: [
            {
                type: 'type1',
                content: 'Test 1',
            },
        ],
    });

    expect(blockCollection.props().value.length).toBe(1);
    expect(blockCollection.instance().expandedBlocks.length).toBe(1);
    expect(blockCollection.instance().generatedBlockIds.length).toBe(1);
    expect(blockCollection.instance().expandedBlocks[0]).toBe(true);
});

test('Should adjust expandedBlocks and generatedBlockIds after updating the value variable with more entries', () => {
    const types = {
        type1: 'Type 1',
        type2: 'Type 2',
    };

    const value = [
        {
            type: 'type1',
            content: 'Test 1',
        },
    ];

    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            types={types}
            value={value}
        />
    );

    blockCollection.instance().expandedBlocks[0] = true;

    expect(blockCollection.props().value.length).toBe(1);
    expect(blockCollection.instance().expandedBlocks.length).toBe(1);
    expect(blockCollection.instance().generatedBlockIds.length).toBe(1);
    expect(blockCollection.instance().expandedBlocks[0]).toBe(true);

    blockCollection.setProps({
        value: [
            {
                type: 'type1',
                content: 'Test 1',
            },
            {
                type: 'type2',
                content: 'Test 2',
            },
            {
                type: 'type1',
                content: 'Test 3',
            },
        ],
    });

    expect(blockCollection.props().value.length).toBe(3);
    expect(blockCollection.instance().expandedBlocks.length).toBe(3);
    expect(blockCollection.instance().generatedBlockIds.length).toBe(3);
    expect(blockCollection.instance().expandedBlocks[0]).toBe(true);
});

test('Updating value with same length should not adjust expandedBlocks and generatedBlockIds', () => {
    const types = {
        type1: 'Type 1',
        type2: 'Type 2',
    };

    const value = [
        {
            type: 'type1',
            content: 'Test 1',
        },
        {
            type: 'type2',
            content: 'Test 2',
        },
        {
            type: 'type1',
            content: 'Test 3',
        },
    ];

    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            types={types}
            value={value}
        />
    );

    blockCollection.instance().expandedBlocks[0] = true;

    expect(blockCollection.props().value.length).toBe(3);
    expect(blockCollection.instance().expandedBlocks.length).toBe(3);
    expect(blockCollection.instance().generatedBlockIds.length).toBe(3);
    expect(blockCollection.instance().expandedBlocks[0]).toBe(true);

    blockCollection.setProps({
        value: [
            {
                type: 'type2',
                content: 'Test 3',
            },
            {
                type: 'type1',
                content: 'Test 1',
            },
            {
                type: 'type2',
                content: 'Test 2',
            },
        ],
    });

    expect(blockCollection.props().value.length).toBe(3);
    expect(blockCollection.instance().expandedBlocks.length).toBe(3);
    expect(blockCollection.instance().generatedBlockIds.length).toBe(3);
    expect(blockCollection.instance().expandedBlocks[0]).toBe(true);
});

test('Should not show BlockToolbarButton when have no blocks', () => {
    const value = [];
    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            value={value}
        />
    );

    expect(blockCollection.find('button.blockCollectionActionButton').length).toBe(0);
    expect(blockCollection.find('BlockToolbar').length).toBe(0);
});

test('Should not show BlockToolbarButton when have only one block', () => {
    const types = {
        type1: 'Type 1',
    };

    const value = [
        {
            type: 'type1',
            content: 'Test 1',
        },
    ];

    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            types={types}
            value={value}
        />
    );

    expect(blockCollection.find('button.blockCollectionActionButton').length).toBe(0);
    expect(blockCollection.find('BlockToolbar').length).toBe(0);
});

test('Should show BlockToolbarButton when have two or more blocks', () => {
    const types = {
        type1: 'Type 1',
        type2: 'Type 2',
    };

    const value = [
        {
            type: 'type1',
            content: 'Test 1',
        },
        {
            type: 'type2',
            content: 'Test 2',
        },
    ];

    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            types={types}
            value={value}
        />
    );

    expect(blockCollection.find('.blockCollectionActionButtonContainer').length).toBe(1);
    expect(blockCollection.find('BlockToolbar').length).toBe(0);
});

test('Show BlockToolbar when select multiple button is clicked', () => {
    const types = {
        type1: 'Type 1',
        type2: 'Type 2',
    };

    const value = [
        {
            type: 'type1',
            content: 'Test 1',
        },
        {
            type: 'type2',
            content: 'Test 2',
        },
    ];

    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            types={types}
            value={value}
        />
    );

    const blockCollectionActionButton = blockCollection.find('button.blockCollectionActionButton');

    blockCollectionActionButton.first().simulate('click');

    expect(blockCollection.find('button.blockCollectionActionButton').length).toBe(0);
    expect(blockCollection.find('BlockToolbar').length).toBe(1);
});

test('Hide BlockToolbar when cancel of BlockToolbar is clicked', () => {
    const types = {
        type1: 'Type 1',
        type2: 'Type 2',
    };

    const value = [
        {
            type: 'type1',
            content: 'Test 1',
        },
        {
            type: 'type2',
            content: 'Test 2',
        },
    ];

    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            types={types}
            value={value}
        />
    );

    const blockCollectionActionButton = blockCollection.find('button.blockCollectionActionButton');
    blockCollectionActionButton.first().simulate('click');
    const blockToolbar = blockCollection.find('BlockToolbar');

    blockToolbar.find('button').last().simulate('click');

    expect(blockCollection.find('.blockCollectionActionButtonContainer').length).toBe(1);
});

test('Show selection handle when BlockToolbar is open', () => {
    const types = {
        type1: 'Type 1',
        type2: 'Type 2',
    };

    const value = [
        {
            type: 'type1',
            content: 'Test 1',
        },
        {
            type: 'type2',
            content: 'Test 2',
        },
    ];

    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            types={types}
            value={value}
        />
    );

    expect(blockCollection.find('SelectionHandle').length).toBe(0);

    const blockCollectionActionButton = blockCollection.find('button.blockCollectionActionButton');
    blockCollectionActionButton.first().simulate('click');

    expect(blockCollection.find('SelectionHandle').length).toBe(2);
});

test('Count selected blocks in BlockToolbar', () => {
    const types = {
        type1: 'Type 1',
        type2: 'Type 2',
    };

    const value = [
        {
            type: 'type1',
            content: 'Test 1',
        },
        {
            type: 'type2',
            content: 'Test 2',
        },
    ];

    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            types={types}
            value={value}
        />
    );

    const blockCollectionActionButton = blockCollection.find('button.blockCollectionActionButton');
    blockCollectionActionButton.first().simulate('click');

    expect(blockCollection.find('BlockToolbar').props().selectedCount).toBe(0);

    const selectionHandles = blockCollection.find('SelectionHandle');

    selectionHandles.first().simulate('click');
    selectionHandles.last().simulate('click');

    expect(blockCollection.find('BlockToolbar').props().selectedCount).toBe(2);
});

test('Copy selected blocks via the BlockToolbar', () => {
    const changeSpy = jest.fn();
    const clipboardSpy = jest.fn();
    clipboard.observe('blocks', clipboardSpy);

    const types = {
        type1: 'Type 1',
        type2: 'Type 2',
    };

    const value = [
        {
            type: 'type1',
            content: 'Test 1',
        },
        {
            type: 'type2',
            content: 'Test 2',
        },
    ];

    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={changeSpy}
            renderBlockContent={jest.fn()}
            types={types}
            value={value}
        />
    );

    const blockCollectionActionButton = blockCollection.find('button.blockCollectionActionButton');
    blockCollectionActionButton.first().simulate('click');
    const blockToolbar = blockCollection.find('BlockToolbar');
    const selectionHandles = blockCollection.find('SelectionHandle');
    selectionHandles.first().simulate('click');
    selectionHandles.last().simulate('click');

    blockToolbar.find('button[aria-label="sulu_admin.copy"]').simulate('click');

    expect(clipboardSpy).toBeCalledWith(value);
    expect(changeSpy).not.toBeCalled();
});

test('Duplicate selected blocks via the BlockToolbar', () => {
    const changeSpy = jest.fn();
    const clipboardSpy = jest.fn();
    clipboard.observe('blocks', clipboardSpy);

    const types = {
        type1: 'Type 1',
        type2: 'Type 2',
    };

    const value = [
        {
            type: 'type1',
            content: 'Test 1',
        },
        {
            type: 'type2',
            content: 'Test 2',
        },
    ];

    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={changeSpy}
            renderBlockContent={jest.fn()}
            types={types}
            value={value}
        />
    );

    const blockCollectionActionButton = blockCollection.find('button.blockCollectionActionButton');
    blockCollectionActionButton.first().simulate('click');
    const blockToolbar = blockCollection.find('BlockToolbar');
    const selectionHandles = blockCollection.find('SelectionHandle');
    selectionHandles.first().simulate('click');
    selectionHandles.last().simulate('click');

    blockToolbar.find('button[aria-label="sulu_admin.duplicate"]').simulate('click');

    expect(clipboardSpy).not.toBeCalled();
    expect(changeSpy).toBeCalledWith([...value, ...value]);
});

test('Cut selected blocks via the BlockToolbar', () => {
    const changeSpy = jest.fn();
    const clipboardSpy = jest.fn();
    clipboard.observe('blocks', clipboardSpy);

    const types = {
        type1: 'Type 1',
        type2: 'Type 2',
    };

    const value = [
        {
            type: 'type1',
            content: 'Test 1',
        },
        {
            type: 'type2',
            content: 'Test 2',
        },
    ];

    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={changeSpy}
            renderBlockContent={jest.fn()}
            types={types}
            value={value}
        />
    );

    const blockCollectionActionButton = blockCollection.find('button.blockCollectionActionButton');
    blockCollectionActionButton.first().simulate('click');
    const blockToolbar = blockCollection.find('BlockToolbar');
    const selectionHandles = blockCollection.find('SelectionHandle');
    selectionHandles.first().simulate('click');
    selectionHandles.last().simulate('click');

    blockToolbar.find('button[aria-label="sulu_admin.cut"]').simulate('click');

    expect(clipboardSpy).toBeCalledWith(value);
    expect(changeSpy).toBeCalledWith([]);
});

test('Remove selected blocks via the BlockToolbar', () => {
    const changeSpy = jest.fn();
    const clipboardSpy = jest.fn();
    clipboard.observe('blocks', clipboardSpy);

    const types = {
        type1: 'Type 1',
        type2: 'Type 2',
    };

    const value = [
        {
            type: 'type1',
            content: 'Test 1',
        },
        {
            type: 'type2',
            content: 'Test 2',
        },
    ];

    const blockCollection = mount(
        <BlockCollection
            defaultType="editor"
            onChange={changeSpy}
            renderBlockContent={jest.fn()}
            types={types}
            value={value}
        />
    );

    const blockCollectionActionButton = blockCollection.find('button.blockCollectionActionButton');
    blockCollectionActionButton.first().simulate('click');
    const blockToolbar = blockCollection.find('BlockToolbar');
    const selectionHandles = blockCollection.find('SelectionHandle');
    selectionHandles.first().simulate('click');
    selectionHandles.last().simulate('click');

    blockToolbar.find('button[aria-label="sulu_admin.delete"]').simulate('click');

    expect(clipboardSpy).not.toBeCalled();
    expect(changeSpy).toBeCalledWith([]);
});
