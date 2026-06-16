// @flow
import {act, render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import {observable} from 'mobx';
import BlockCollection from '../BlockCollection';
import clipboard from '../../../utils/clipboard/clipboard';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

type RenderBlockCollectionResult = {
    container: HTMLElement,
    getCurrentProps: () => Object,
    ref: {current: any},
    rerenderBlockCollection: (nextProps: Object) => void,
    user: any,
};

function renderBlockCollection(props: Object = {}): RenderBlockCollectionResult {
    const ref: any = React.createRef();
    let currentProps = {
        defaultType: 'editor',
        onChange: jest.fn(),
        renderBlockContent: jest.fn(),
        value: [],
        ...props,
    };

    const {container, rerender} = render(
        <BlockCollection
            {...currentProps}
            ref={ref}
        />
    );

    return {
        container,
        ref,
        rerenderBlockCollection: (nextProps: Object) => {
            currentProps = {...currentProps, ...nextProps};

            rerender(
                <BlockCollection
                    {...currentProps}
                    ref={ref}
                />
            );
        },
        user: userEvent.setup(),
        getCurrentProps: () => currentProps,
    };
}

function getBlocks() {
    return screen.queryAllByRole('switch');
}

function getBlock(index: number) {
    const block = getBlocks()[index];

    if (!block) {
        throw new Error(`Expected block at index ${index}`);
    }

    return block;
}

function isBlockExpanded(index: number) {
    return getBlock(index).classList.contains('expanded');
}

function getButtonsByName(name: string) {
    return screen.queryAllByRole('button', {name: new RegExp(name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'))});
}

function getButtonByName(name: string) {
    return screen.getByRole('button', {name: new RegExp(name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'))});
}

function queryButtonByName(name: string) {
    return screen.queryByRole('button', {name: new RegExp(name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'))});
}

function getSelectionHandleCheckboxes() {
    return getBlocks()
        .map((block) => within(block).queryByRole('checkbox'))
        .filter(Boolean);
}

async function openBlockActions(user: any, index: number) {
    await user.click(getBlock(index));
    await user.click(within(getBlock(index)).getByLabelText('su-more-circle'));
}

beforeEach(() => {
    BlockCollection.idCounter = 0;
    window.localStorage.clear();
    window.localStorage.removeItem('blocks');
});

afterEach(() => {
    window.localStorage.removeItem('blocks');
});

test('Should render a fully filled block list', () => {
    const {asFragment} = render(
        <BlockCollection
            defaultType="editor"
            icons={[[], ['su-eye']]}
            maxOccurs={3}
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            value={[{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}]}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Should render a non-movable block list', () => {
    const {asFragment} = render(
        <BlockCollection
            collapsable={false}
            defaultType="editor"
            movable={false}
            onChange={jest.fn()}
            renderBlockContent={jest.fn()}
            value={[{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}]}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Should render a disabled block list', () => {
    renderBlockCollection({
        disabled: true,
        value: [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}],
    });

    const blockList = getBlock(0).closest('.sortableBlockList');
    expect(blockList).toHaveClass('disabled');

    const addButtons = getButtonsByName('sulu_admin.add_block');
    expect(addButtons[addButtons.length - 1]).toBeDisabled();
});

test('Should mark the add button disabled if maxOccurs is reached', () => {
    renderBlockCollection({
        maxOccurs: 2,
        value: [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}],
    });

    expect(getButtonsByName('sulu_admin.add_block')[0]).toBeDisabled();
});

test('Should render add button with the given addButtonText', () => {
    renderBlockCollection({
        addButtonText: 'custom-add-button-text',
        maxOccurs: 2,
        value: [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}],
    });

    expect(getButtonsByName('custom-add-button-text')[0]).toBeInTheDocument();
});

test('Should render paste button if clipboard contains a block', () => {
    renderBlockCollection({
        maxOccurs: 2,
        value: [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}],
    });

    expect(getButtonsByName('sulu_admin.paste_blocks')).toHaveLength(0);

    act(() => {
        clipboard.set('blocks', [{content: 'Test 3', type: 'editor'}]);
    });

    const pasteButtons = getButtonsByName('sulu_admin.paste_blocks');
    expect(pasteButtons.length).toBeGreaterThan(0);
    expect(pasteButtons[0]).toHaveTextContent('sulu_admin.paste_blocks');
});

test('Should render paste button with the given pasteButtonText', () => {
    clipboard.set('blocks', [{content: 'Test 3', type: 'editor'}]);

    renderBlockCollection({
        maxOccurs: 2,
        pasteButtonText: 'custom-paste-button-text',
        value: [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}],
    });

    const pasteButtons = getButtonsByName('custom-paste-button-text');
    expect(pasteButtons.length).toBeGreaterThan(0);
    expect(pasteButtons[0]).toHaveTextContent('custom-paste-button-text');
});

test('Should add at least the minOccurs amount of blocks', () => {
    const changeSpy = jest.fn();
    const value = [{type: 'editor'}];

    renderBlockCollection({
        minOccurs: 2,
        onChange: changeSpy,
        value,
    });

    expect(changeSpy).toHaveBeenCalledWith([
        expect.objectContaining({}),
        expect.objectContaining({}),
    ]);
});

test('Should fill the array up to minOccurs with different objects', () => {
    const changeSpy = jest.fn();
    const value = [];

    renderBlockCollection({
        minOccurs: 2,
        onChange: changeSpy,
        value,
    });

    expect(changeSpy).toHaveBeenCalledWith([
        expect.objectContaining({}),
        expect.objectContaining({}),
    ]);
    const changeSpyCall = changeSpy.mock.calls[0][0];
    expect(changeSpyCall[0]).not.toBe(changeSpyCall[1]);
});

test('Should add at least the minOccurs amount of blocks with empty starting value', () => {
    const changeSpy = jest.fn();
    const value = [];

    renderBlockCollection({
        minOccurs: 2,
        onChange: changeSpy,
        value,
    });

    expect(changeSpy).toHaveBeenCalledWith([
        expect.objectContaining({}),
        expect.objectContaining({}),
    ]);
});

test('Should add at least the minOccurs amount of blocks with types', () => {
    const changeSpy = jest.fn();
    const value = [{type: 'default'}];

    renderBlockCollection({
        minOccurs: 2,
        onChange: changeSpy,
        types: {default: 'Default', editor: 'Editor'},
        value,
    });

    expect(changeSpy).toHaveBeenCalledWith([
        expect.objectContaining({type: 'default'}),
        expect.objectContaining({type: 'editor'}),
    ]);
});

test('Should generate IDs for minOccurs blocks with generateBlockIds enabled', async() => {
    const mockIds = ['id1', 'id2', 'id3'];
    const generateBlockIdsSpy = jest.fn().mockReturnValue(Promise.resolve(mockIds));
    const changeSpy = jest.fn();
    const value = [];

    renderBlockCollection({
        generateBlockIds: generateBlockIdsSpy,
        minOccurs: 3,
        onChange: changeSpy,
        value,
    });

    // Wait for async fillArrays to complete
    await act(async() => {
        await new Promise((resolve) => setTimeout(resolve, 0));
    });

    expect(generateBlockIdsSpy).toHaveBeenCalledWith(3);
    expect(changeSpy).toHaveBeenCalledWith([
        {type: 'editor', _id: 'id1'},
        {type: 'editor', _id: 'id2'},
        {type: 'editor', _id: 'id3'},
    ]);
});

test('Choosing a different type should call the onChange callback', async() => {
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
    const {user} = renderBlockCollection({
        onChange: changeSpy,
        renderBlockContent,
        types: {type1: 'Type 1', type2: 'Type2'},
        value,
    });

    await user.click(getBlock(0));
    await user.click(getBlock(1));

    expect(within(getBlock(0)).getByRole('button', {name: /Type 1/})).toBeInTheDocument();
    expect(within(getBlock(1)).getByRole('button', {name: /Type2/})).toBeInTheDocument();

    await user.click(within(getBlock(0)).getByRole('button', {name: /Type 1/}));
    await user.click(screen.getByRole('button', {name: 'Type2'}));

    expect(changeSpy).toHaveBeenCalledWith([
        expect.objectContaining({content: 'Test 1', type: 'type2'}),
        expect.objectContaining({content: 'Test 2', type: 'type2'}),
    ]);
});

test('Should allow to expand blocks', async() => {
    const {user} = renderBlockCollection({
        value: [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}],
    });

    expect(isBlockExpanded(0)).toEqual(false);
    expect(isBlockExpanded(1)).toEqual(false);

    await user.click(getBlock(1));

    expect(isBlockExpanded(0)).toEqual(false);
    expect(isBlockExpanded(1)).toEqual(true);
});

test('Should allow to collapse blocks', async() => {
    const {user} = renderBlockCollection({
        value: [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}],
    });

    await user.click(getBlock(0));
    await user.click(getBlock(1));

    expect(isBlockExpanded(0)).toEqual(true);
    expect(isBlockExpanded(1)).toEqual(true);

    await user.click(within(getBlock(0)).getByLabelText('su-collapse-vertical'));

    expect(isBlockExpanded(0)).toEqual(false);
    expect(isBlockExpanded(1)).toEqual(true);
});

test('Should allow to collapse all blocks', async() => {
    const {user} = renderBlockCollection({
        value: [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}],
    });

    await user.click(getBlock(0));
    await user.click(getBlock(1));

    expect(isBlockExpanded(0)).toEqual(true);
    expect(isBlockExpanded(1)).toEqual(true);

    await user.click(getButtonByName('sulu_admin.collapse_all_blocks'));

    expect(isBlockExpanded(0)).toEqual(false);
    expect(isBlockExpanded(1)).toEqual(false);
});

test('Should allow to expand all blocks', async() => {
    const {user} = renderBlockCollection({
        value: [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}],
    });

    expect(isBlockExpanded(0)).toEqual(false);
    expect(isBlockExpanded(1)).toEqual(false);

    await user.click(getButtonByName('sulu_admin.expand_all_blocks'));

    expect(isBlockExpanded(0)).toEqual(true);
    expect(isBlockExpanded(1)).toEqual(true);
});

test('Should allow to reorder blocks by using drag and drop', async() => {
    const changeSpy = jest.fn();
    const sortEndSpy = jest.fn();
    const value = [
        {content: 'Test 1', type: 'editor'},
        {content: 'Test 2', type: 'editor'},
        {content: 'Test 3', type: 'editor'},
    ];
    const {ref, user} = renderBlockCollection({
        onChange: changeSpy,
        onSortEnd: sortEndSpy,
        value,
    });

    await user.click(getBlock(0));

    expect(Array.from(ref.current.expandedBlocks)).toEqual([true, false, false]);
    expect(Array.from(ref.current.generatedBlockIds)).toEqual([1, 2, 3]);

    act(() => {
        ref.current.handleSortEnd({newIndex: 2, oldIndex: 0});
    });
    expect(changeSpy).toHaveBeenCalledWith([
        expect.objectContaining({content: 'Test 2'}),
        expect.objectContaining({content: 'Test 3'}),
        expect.objectContaining({content: 'Test 1'}),
    ]);
    expect(sortEndSpy).toHaveBeenCalledWith(0, 2);

    expect(Array.from(ref.current.expandedBlocks)).toEqual([false, false, true]);
    expect(Array.from(ref.current.generatedBlockIds)).toEqual([2, 3, 1]);
});

test('Should add a new block between existing blocks', async() => {
    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];
    const {user} = renderBlockCollection({
        onChange: changeSpy,
        value,
    });

    await user.click(getButtonsByName('sulu_admin.add_block')[0]);

    expect(changeSpy).toHaveBeenCalledWith([
        {content: 'Test 1', type: 'editor'},
        {type: 'editor'},
        {content: 'Test 2', type: 'editor'},
    ]);
});

test('Should add a new block at the end', async() => {
    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];
    const {user} = renderBlockCollection({
        onChange: changeSpy,
        value,
    });

    const addButtons = getButtonsByName('sulu_admin.add_block');
    await user.click(addButtons[addButtons.length - 1]);

    expect(changeSpy).toHaveBeenCalledWith([...value, {type: 'editor'}]);
});

test('Should throw an exception if a new block is added and the maximum has already been reached', async() => {
    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];

    const {ref} = renderBlockCollection({
        maxOccurs: 2,
        onChange: changeSpy,
        value,
    });

    await expect(ref.current.handleAddBlock()).rejects.toThrow(/maximum amount of blocks/);
});

test('Should paste block between existing blocks', async() => {
    clipboard.set('blocks', [{content: 'Clipboard', type: 'editor'}]);

    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];
    const {user} = renderBlockCollection({
        onChange: changeSpy,
        value,
    });

    await user.click(getButtonsByName('sulu_admin.paste_blocks')[0]);

    expect(changeSpy).toHaveBeenCalledWith([
        {content: 'Test 1', type: 'editor'},
        {content: 'Clipboard', type: 'editor'},
        {content: 'Test 2', type: 'editor'},
    ]);
});

test('Should paste block at the end', async() => {
    clipboard.set('blocks', [{content: 'Clipboard', type: 'editor'}]);

    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];
    const {user} = renderBlockCollection({
        onChange: changeSpy,
        value,
    });

    const pasteButtons = getButtonsByName('sulu_admin.paste_blocks');
    await user.click(pasteButtons[pasteButtons.length - 1]);

    expect(changeSpy).toHaveBeenCalledWith([...value, {content: 'Clipboard', type: 'editor'}]);
});

test('Should paste block with default type if type of block in clipboard block is not known', async() => {
    clipboard.set('blocks', [{content: 'Clipboard', type: 'unkown-type'}]);

    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];
    const {user} = renderBlockCollection({
        onChange: changeSpy,
        value,
    });

    const pasteButtons = getButtonsByName('sulu_admin.paste_blocks');
    await user.click(pasteButtons[pasteButtons.length - 1]);

    expect(changeSpy).toHaveBeenCalledWith([...value, {content: 'Clipboard', type: 'editor'}]);
});

test('Should throw an exception if a block is pasted and the maximum has already been reached', async() => {
    clipboard.set('blocks', [{content: 'Clipboard', type: 'editor'}]);

    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];

    const {ref} = renderBlockCollection({
        maxOccurs: 2,
        onChange: changeSpy,
        value,
    });

    await expect(ref.current.handlePasteBlocks()).rejects.toThrow(/maximum amount of blocks/);
});

test('Should pass duplicate action that allows to duplicate an existing block', async() => {
    // update value that is passed to the component when change callback is fired to prevent warnings
    const value: any = observable([{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}]);
    const changeSpy = jest.fn().mockImplementation((newValue) => {
        value.splice(0, value.length);
        value.push(...newValue);
    });
    const {user} = renderBlockCollection({
        onChange: changeSpy,
        value,
    });

    await openBlockActions(user, 0);
    expect(getButtonByName('sulu_admin.duplicate')).toBeInTheDocument();

    await user.click(getButtonByName('sulu_admin.duplicate'));

    expect(changeSpy).toHaveBeenCalledWith([
        {content: 'Test 1', type: 'editor'},
        {content: 'Test 1', type: 'editor'},
        {content: 'Test 2', type: 'editor'},
    ]);
});

test('Should not pass duplicate action to Block component if maxOccurs limit is reached', async() => {
    const value = [{content: 'Value 1', type: 'editor'}, {content: 'Value 2', type: 'editor'}];

    const {user} = renderBlockCollection({
        maxOccurs: 2,
        value,
    });

    await openBlockActions(user, 0);

    expect(queryButtonByName('sulu_admin.duplicate')).not.toBeInTheDocument();
});

test('Should throw an exception if a block is duplicated and maxOccurs limit is reached', async() => {
    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];

    const {ref} = renderBlockCollection({
        maxOccurs: 2,
        onChange: changeSpy,
        value,
    });

    await expect(ref.current.duplicateBlocks([0], 0)).rejects.toThrow(/maximum amount of blocks/);
});

test('Should pass remove action that allows to remove an existing block', async() => {
    // update value that is passed to the component when change callback is fired to prevent warnings
    const value: any = observable([{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}]);
    const changeSpy = jest.fn().mockImplementation((newValue) => {
        value.splice(0, value.length);
        value.push(...newValue);
    });
    const {user} = renderBlockCollection({
        onChange: changeSpy,
        value,
    });

    await openBlockActions(user, 0);
    expect(getButtonByName('sulu_admin.delete')).toBeInTheDocument();

    await user.click(getButtonByName('sulu_admin.delete'));

    expect(changeSpy).toHaveBeenCalledWith([expect.objectContaining({content: 'Test 2'})]);
});

test('Should not pass remove action to Block component if minOccurs limit is reached', async() => {
    const value = [{content: 'Value 1', type: 'editor'}, {content: 'Value 2', type: 'editor'}];

    const {user} = renderBlockCollection({
        minOccurs: 2,
        value,
    });

    await openBlockActions(user, 0);

    expect(queryButtonByName('sulu_admin.delete')).not.toBeInTheDocument();
});

test('Should throw an exception if a block is removed and minOccurs limit is reached', () => {
    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];

    const {ref} = renderBlockCollection({
        minOccurs: 2,
        onChange: changeSpy,
        value,
    });

    expect(() => ref.current.handleRemoveBlock(0)).toThrow(/minimum amount of blocks/);
});

test('Should pass copy action that allows to cut an existing block into the clipboard', async() => {
    const clipboardSpy = jest.fn();
    const removeObserver = clipboard.observe('blocks', clipboardSpy);

    const value: any = observable([{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}]);
    const {user} = renderBlockCollection({
        value,
    });

    await openBlockActions(user, 0);
    expect(getButtonByName('sulu_admin.copy')).toBeInTheDocument();

    expect(clipboardSpy).not.toHaveBeenCalled();

    await user.click(getButtonByName('sulu_admin.copy'));

    expect(clipboardSpy).toHaveBeenCalledWith([value[0]]);

    removeObserver();
});

test('Should pass cut action that allows to cut an existing block into the clipboard', async() => {
    const clipboardSpy = jest.fn();
    const removeObserver = clipboard.observe('blocks', clipboardSpy);

    // update value that is passed to the component when change callback is fired to prevent warnings
    const value: any = observable([{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}]);
    const changeSpy = jest.fn().mockImplementation((newValue) => {
        value.splice(0, value.length);
        value.push(...newValue);
    });
    const {user} = renderBlockCollection({
        onChange: changeSpy,
        value,
    });

    await openBlockActions(user, 0);
    expect(getButtonByName('sulu_admin.cut')).toBeInTheDocument();

    expect(clipboardSpy).not.toHaveBeenCalled();

    await user.click(getButtonByName('sulu_admin.cut'));

    expect(clipboardSpy).toHaveBeenCalledWith([expect.objectContaining({content: 'Test 1'})]);
    expect(changeSpy).toHaveBeenCalledWith([expect.objectContaining({content: 'Test 2'})]);

    removeObserver();
});

test('Should not pass cut action to Block component if minOccurs limit is reached', async() => {
    const value = [{content: 'Value 1', type: 'editor'}, {content: 'Value 2', type: 'editor'}];

    const {user} = renderBlockCollection({
        minOccurs: 2,
        value,
    });

    await openBlockActions(user, 0);

    expect(queryButtonByName('sulu_admin.cut')).not.toBeInTheDocument();
});

test('Should throw an exception if a block is removed and minOccurs limit is reached', () => {
    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];

    const {ref} = renderBlockCollection({
        minOccurs: 2,
        onChange: changeSpy,
        value,
    });

    expect(() => ref.current.handleCutBlock(0)).toThrow(/minimum amount of blocks/);
});

test('Should call onSettingsClick callback when settings icon is clicked', async() => {
    const settingsClickSpy = jest.fn();
    const value: any = observable([{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}]);
    const {user} = renderBlockCollection({
        onSettingsClick: settingsClickSpy,
        value,
    });

    await user.click(getBlock(0));
    await user.click(getBlock(1));

    const settingsIcons = screen.getAllByLabelText('su-cog');

    await user.click(settingsIcons[0]);
    expect(settingsClickSpy).toHaveBeenLastCalledWith(0);

    await user.click(settingsIcons[1]);
    expect(settingsClickSpy).toHaveBeenLastCalledWith(1);
});

test('Should apply renderBlockContent before rendering the block content', async() => {
    const prefix = 'This is the test for ';
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];
    const renderBlockContent = jest.fn().mockImplementation((value) => prefix + value.content);
    const {user} = renderBlockCollection({
        renderBlockContent,
        value,
    });

    await user.click(getBlock(0));
    await user.click(getBlock(1));

    expect(within(getBlock(0)).getByText(prefix + value[0].content)).toBeInTheDocument();
    expect(within(getBlock(1)).getByText(prefix + value[1].content)).toBeInTheDocument();
});

test('Should apply renderBlockContent before rendering the block content including the type', async() => {
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

    const {user} = renderBlockCollection({
        renderBlockContent,
        types,
        value,
    });

    await user.click(getBlock(0));
    await user.click(getBlock(1));

    expect(within(getBlock(0)).getByText(prefix + value[0].content + typePrefix + 'type2')).toBeInTheDocument();
    expect(within(getBlock(1)).getByText(prefix + value[1].content + typePrefix + 'type1')).toBeInTheDocument();
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

    const blockCollection = renderBlockCollection({
        types,
        value,
    });

    blockCollection.ref.current.expandedBlocks[0] = true;

    expect(blockCollection.getCurrentProps().value.length).toBe(3);
    expect(blockCollection.ref.current.expandedBlocks.length).toBe(3);
    expect(blockCollection.ref.current.generatedBlockIds.length).toBe(3);
    expect(blockCollection.ref.current.expandedBlocks[0]).toBe(true);

    blockCollection.rerenderBlockCollection({
        value: [
            {
                type: 'type1',
                content: 'Test 1',
            },
        ],
    });

    expect(blockCollection.getCurrentProps().value.length).toBe(1);
    expect(blockCollection.ref.current.expandedBlocks.length).toBe(1);
    expect(blockCollection.ref.current.generatedBlockIds.length).toBe(1);
    expect(blockCollection.ref.current.expandedBlocks[0]).toBe(true);
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

    const blockCollection = renderBlockCollection({
        types,
        value,
    });

    blockCollection.ref.current.expandedBlocks[0] = true;

    expect(blockCollection.getCurrentProps().value.length).toBe(1);
    expect(blockCollection.ref.current.expandedBlocks.length).toBe(1);
    expect(blockCollection.ref.current.generatedBlockIds.length).toBe(1);
    expect(blockCollection.ref.current.expandedBlocks[0]).toBe(true);

    blockCollection.rerenderBlockCollection({
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

    expect(blockCollection.getCurrentProps().value.length).toBe(3);
    expect(blockCollection.ref.current.expandedBlocks.length).toBe(3);
    expect(blockCollection.ref.current.generatedBlockIds.length).toBe(3);
    expect(blockCollection.ref.current.expandedBlocks[0]).toBe(true);
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

    const blockCollection = renderBlockCollection({
        types,
        value,
    });

    blockCollection.ref.current.expandedBlocks[0] = true;

    expect(blockCollection.getCurrentProps().value.length).toBe(3);
    expect(blockCollection.ref.current.expandedBlocks.length).toBe(3);
    expect(blockCollection.ref.current.generatedBlockIds.length).toBe(3);
    expect(blockCollection.ref.current.expandedBlocks[0]).toBe(true);

    blockCollection.rerenderBlockCollection({
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

    expect(blockCollection.getCurrentProps().value.length).toBe(3);
    expect(blockCollection.ref.current.expandedBlocks.length).toBe(3);
    expect(blockCollection.ref.current.generatedBlockIds.length).toBe(3);
    expect(blockCollection.ref.current.expandedBlocks[0]).toBe(true);
});

test('Should not show BlockToolbarButton when have no blocks', () => {
    const value = [];
    renderBlockCollection({
        value,
    });

    expect(queryButtonByName('sulu_admin.select_multiple_blocks')).not.toBeInTheDocument();
    expect(queryButtonByName('sulu_admin.cancel')).not.toBeInTheDocument();
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

    renderBlockCollection({
        types,
        value,
    });

    expect(queryButtonByName('sulu_admin.select_multiple_blocks')).not.toBeInTheDocument();
    expect(queryButtonByName('sulu_admin.cancel')).not.toBeInTheDocument();
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

    renderBlockCollection({
        types,
        value,
    });

    expect(getButtonByName('sulu_admin.select_multiple_blocks')).toBeInTheDocument();
    expect(queryButtonByName('sulu_admin.cancel')).not.toBeInTheDocument();
});

test('Show BlockToolbar when select multiple button is clicked', async() => {
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

    const {user} = renderBlockCollection({
        types,
        value,
    });

    await user.click(getButtonByName('sulu_admin.select_multiple_blocks'));

    expect(queryButtonByName('sulu_admin.select_multiple_blocks')).not.toBeInTheDocument();
    expect(getButtonByName('sulu_admin.cancel')).toBeInTheDocument();
});

test('Hide BlockToolbar when cancel of BlockToolbar is clicked', async() => {
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

    const {user} = renderBlockCollection({
        types,
        value,
    });

    await user.click(getButtonByName('sulu_admin.select_multiple_blocks'));
    await user.click(getButtonByName('sulu_admin.cancel'));

    expect(getButtonByName('sulu_admin.select_multiple_blocks')).toBeInTheDocument();
});

test('Show selection handle when BlockToolbar is open', async() => {
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

    const {user} = renderBlockCollection({
        types,
        value,
    });

    expect(getSelectionHandleCheckboxes()).toHaveLength(0);

    await user.click(getButtonByName('sulu_admin.select_multiple_blocks'));

    expect(getSelectionHandleCheckboxes()).toHaveLength(2);
});

test('Count selected blocks in BlockToolbar', async() => {
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

    const {user} = renderBlockCollection({
        types,
        value,
    });

    await user.click(getButtonByName('sulu_admin.select_multiple_blocks'));

    const copyButton = getButtonByName('sulu_admin.copy');
    expect(copyButton).toBeDisabled();

    const selectionHandleCheckboxes = getSelectionHandleCheckboxes();
    await user.click(selectionHandleCheckboxes[0]);
    await user.click(selectionHandleCheckboxes[1]);

    expect(copyButton).toBeEnabled();
});

test('Copy selected blocks via the BlockToolbar', async() => {
    const changeSpy = jest.fn();
    const clipboardSpy = jest.fn();
    const removeObserver = clipboard.observe('blocks', clipboardSpy);

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

    const {user} = renderBlockCollection({
        onChange: changeSpy,
        types,
        value,
    });

    await user.click(getButtonByName('sulu_admin.select_multiple_blocks'));
    const selectionHandleCheckboxes = getSelectionHandleCheckboxes();
    await user.click(selectionHandleCheckboxes[0]);
    await user.click(selectionHandleCheckboxes[1]);

    await user.click(getButtonByName('sulu_admin.copy'));

    expect(clipboardSpy).toHaveBeenCalledWith(value);
    expect(changeSpy).not.toHaveBeenCalled();

    removeObserver();
});

test('Duplicate selected blocks via the BlockToolbar', async() => {
    const changeSpy = jest.fn();
    const clipboardSpy = jest.fn();
    const removeObserver = clipboard.observe('blocks', clipboardSpy);

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

    const {user} = renderBlockCollection({
        onChange: changeSpy,
        types,
        value,
    });

    await user.click(getButtonByName('sulu_admin.select_multiple_blocks'));
    const selectionHandleCheckboxes = getSelectionHandleCheckboxes();
    await user.click(selectionHandleCheckboxes[0]);
    await user.click(selectionHandleCheckboxes[1]);

    await user.click(getButtonByName('sulu_admin.duplicate'));

    expect(clipboardSpy).not.toHaveBeenCalled();
    expect(changeSpy).toHaveBeenCalledWith([...value, ...value]);

    removeObserver();
});

test('Duplicate multiple non-contiguous blocks correctly', async() => {
    const changeSpy = jest.fn();
    const generateBlockIdsSpy = jest.fn().mockResolvedValue(['id1', 'id2']);

    const types = {
        type1: 'Type 1',
        type2: 'Type 2',
        type3: 'Type 3',
        type4: 'Type 4',
    };

    const value = [
        {type: 'type1', content: 'Block 0'},
        {type: 'type2', content: 'Block 1'},
        {type: 'type3', content: 'Block 2'},
        {type: 'type4', content: 'Block 3'},
    ];

    const {ref} = renderBlockCollection({
        generateBlockIds: generateBlockIdsSpy,
        onChange: changeSpy,
        types,
        value,
    });

    await act(async() => {
        await ref.current.duplicateBlocks([0, 2], value.length);
    });

    expect(generateBlockIdsSpy).toHaveBeenCalledWith(2);
    expect(changeSpy).toHaveBeenCalledTimes(1);

    const newValue = changeSpy.mock.calls[0][0];
    expect(newValue).toHaveLength(6);
    expect(newValue[0].content).toBe('Block 0');
    expect(newValue[1].content).toBe('Block 1');
    expect(newValue[2].content).toBe('Block 2');
    expect(newValue[3].content).toBe('Block 3');
    expect(newValue[4].content).toBe('Block 0');
    expect(newValue[4]._id).toBe('id1');
    expect(newValue[5].content).toBe('Block 2');
    expect(newValue[5]._id).toBe('id2');
});

test('Duplicate single block maintains correct position', async() => {
    const changeSpy = jest.fn();

    const types = {
        type1: 'Type 1',
        type2: 'Type 2',
        type3: 'Type 3',
    };

    const value = [
        {type: 'type1', content: 'Block 0'},
        {type: 'type2', content: 'Block 1'},
        {type: 'type3', content: 'Block 2'},
    ];

    const {ref} = renderBlockCollection({
        onChange: changeSpy,
        types,
        value,
    });

    await act(async() => {
        await ref.current.duplicateBlocks([1], 1);
    });

    const newValue = changeSpy.mock.calls[0][0];
    expect(newValue).toHaveLength(4);
    expect(newValue[0].content).toBe('Block 0');
    expect(newValue[1].content).toBe('Block 1');
    expect(newValue[2].content).toBe('Block 1');
    expect(newValue[3].content).toBe('Block 2');
});

test('Tracking arrays stay synchronized after block duplication', async() => {
    const changeSpy = jest.fn();

    const types = {
        type1: 'Type 1',
        type2: 'Type 2',
    };

    const value = [
        {type: 'type1', content: 'Block 0'},
        {type: 'type2', content: 'Block 1'},
    ];

    const {ref} = renderBlockCollection({
        onChange: changeSpy,
        types,
        value,
    });

    await act(async() => {
        await ref.current.duplicateBlocks([0, 1], value.length);
    });

    const newValue = changeSpy.mock.calls[0][0];

    expect(ref.current.expandedBlocks).toHaveLength(newValue.length);
    expect(ref.current.selectedBlocks).toHaveLength(newValue.length);
    expect(ref.current.generatedBlockIds).toHaveLength(newValue.length);

    expect(ref.current.expandedBlocks[2]).toBe(true);
    expect(ref.current.expandedBlocks[3]).toBe(true);

    expect(ref.current.selectedBlocks[2]).toBe(false);
    expect(ref.current.selectedBlocks[3]).toBe(false);
});

test('Empty selection does not trigger duplication', async() => {
    const changeSpy = jest.fn();

    const value = [{type: 'type1', content: 'Block 0'}];

    const {ref} = renderBlockCollection({
        onChange: changeSpy,
        types: {type1: 'Type 1'},
        value,
    });

    await act(async() => {
        await ref.current.duplicateBlocks([], 0);
    });

    expect(changeSpy).not.toHaveBeenCalled();
});

test('Cut selected blocks via the BlockToolbar', async() => {
    const changeSpy = jest.fn();
    const clipboardSpy = jest.fn();
    const removeObserver = clipboard.observe('blocks', clipboardSpy);

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

    const {user} = renderBlockCollection({
        onChange: changeSpy,
        types,
        value,
    });

    await user.click(getButtonByName('sulu_admin.select_multiple_blocks'));
    const selectionHandleCheckboxes = getSelectionHandleCheckboxes();
    await user.click(selectionHandleCheckboxes[0]);
    await user.click(selectionHandleCheckboxes[1]);

    await user.click(getButtonByName('sulu_admin.cut'));

    expect(clipboardSpy).toHaveBeenCalledWith(value);
    expect(changeSpy).toHaveBeenCalledWith([]);

    removeObserver();
});

test('Remove selected blocks via the BlockToolbar', async() => {
    const changeSpy = jest.fn();
    const clipboardSpy = jest.fn();
    const removeObserver = clipboard.observe('blocks', clipboardSpy);

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

    const {user} = renderBlockCollection({
        onChange: changeSpy,
        types,
        value,
    });

    await user.click(getButtonByName('sulu_admin.select_multiple_blocks'));
    const selectionHandleCheckboxes = getSelectionHandleCheckboxes();
    await user.click(selectionHandleCheckboxes[0]);
    await user.click(selectionHandleCheckboxes[1]);

    await user.click(getButtonByName('sulu_admin.delete'));

    expect(clipboardSpy).not.toHaveBeenCalled();
    expect(changeSpy).toHaveBeenCalledWith([]);

    removeObserver();
});

test('Should generate ID when adding new block with generateBlockIds enabled', async() => {
    const mockId = 'abc12345';
    const generateBlockIdsSpy = jest.fn().mockReturnValue(Promise.resolve([mockId]));

    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}];
    const {ref} = renderBlockCollection({
        generateBlockIds: generateBlockIdsSpy,
        onChange: changeSpy,
        value,
    });

    await act(async() => {
        await ref.current.handleAddBlock(1);
    });

    expect(generateBlockIdsSpy).toHaveBeenCalledWith(1);
    expect(changeSpy).toHaveBeenCalledWith([
        {content: 'Test 1', type: 'editor'},
        {type: 'editor', _id: mockId},
    ]);
});

test('Should not generate ID when adding block without generateBlockIds', async() => {
    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}];
    const {user} = renderBlockCollection({
        onChange: changeSpy,
        value,
    });

    const addButtons = getButtonsByName('sulu_admin.add_block');
    await user.click(addButtons[addButtons.length - 1]);

    expect(changeSpy).toHaveBeenCalledWith([
        {content: 'Test 1', type: 'editor'},
        {type: 'editor'},
    ]);
});

test('Should create blocks with minimal structure to allow field components to apply defaults', async() => {
    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}];
    const {ref} = renderBlockCollection({
        onChange: changeSpy,
        value,
    });

    await act(async() => {
        await ref.current.handleAddBlock(1);
    });

    // Verify block is created with only type (no field values pre-populated)
    // This allows field components to apply defaults in their constructors
    expect(changeSpy).toHaveBeenCalledWith([
        {content: 'Test 1', type: 'editor'},
        {type: 'editor'},
    ]);
});

test('Should synchronize observables atomically when adding block', async() => {
    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}];
    const {ref} = renderBlockCollection({
        onChange: changeSpy,
        value,
    });

    // Verify observables are empty initially (only for existing block)
    expect(ref.current.expandedBlocks.length).toBe(1);
    expect(ref.current.selectedBlocks.length).toBe(1);
    expect(ref.current.generatedBlockIds.length).toBe(1);

    await act(async() => {
        await ref.current.handleAddBlock(1);
    });

    // After adding block, observables should be updated to match new value length
    expect(ref.current.expandedBlocks.length).toBe(2);
    expect(ref.current.selectedBlocks.length).toBe(2);
    expect(ref.current.generatedBlockIds.length).toBe(2);
});

test('Should synchronize observables atomically when adding block with generateBlockIds', async() => {
    const mockId = 'test123';
    const generateBlockIdsSpy = jest.fn().mockReturnValue(Promise.resolve([mockId]));
    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}];
    const {ref} = renderBlockCollection({
        generateBlockIds: generateBlockIdsSpy,
        onChange: changeSpy,
        value,
    });

    // Capture observable lengths before operation
    const expandedBefore = ref.current.expandedBlocks.length;
    const selectedBefore = ref.current.selectedBlocks.length;
    const generatedIdsBefore = ref.current.generatedBlockIds.length;

    await act(async() => {
        await ref.current.handleAddBlock(1);
    });

    // Observables should be updated atomically AFTER async operation
    // No intermediate state where observables have different lengths than value
    expect(ref.current.expandedBlocks.length).toBe(expandedBefore + 1);
    expect(ref.current.selectedBlocks.length).toBe(selectedBefore + 1);
    expect(ref.current.generatedBlockIds.length).toBe(generatedIdsBefore + 1);

    expect(changeSpy).toHaveBeenCalledWith([
        {content: 'Test 1', type: 'editor'},
        {type: 'editor', _id: mockId},
    ]);
});

test('Should create minOccurs blocks with minimal structure for field component defaults', async() => {
    const changeSpy = jest.fn();
    const value = [];

    renderBlockCollection({
        minOccurs: 2,
        onChange: changeSpy,
        value,
    });

    // fillArrays runs automatically via reaction
    await act(async() => {
        await new Promise((resolve) => setTimeout(resolve, 0));
    });

    // Verify blocks created with only type to allow field components to apply defaults
    expect(changeSpy).toHaveBeenCalledWith([
        {type: 'editor'},
        {type: 'editor'},
    ]);
});

test('Should synchronize observables atomically when creating minOccurs blocks', async() => {
    const changeSpy = jest.fn();
    const value = [];
    const {ref} = renderBlockCollection({
        minOccurs: 3,
        onChange: changeSpy,
        value,
    });

    // fillArrays runs automatically via reaction
    await act(async() => {
        await new Promise((resolve) => setTimeout(resolve, 0));
    });

    // All observables should have same length as value after fillArrays
    expect(ref.current.expandedBlocks.length).toBe(3);
    expect(ref.current.selectedBlocks.length).toBe(3);
    expect(ref.current.generatedBlockIds.length).toBe(3);
});

test('Should generate IDs for minOccurs blocks atomically', async() => {
    const mockIds = ['id1', 'id2', 'id3'];
    const generateBlockIdsSpy = jest.fn().mockReturnValue(Promise.resolve(mockIds));
    const changeSpy = jest.fn();
    const value = [];

    const {ref} = renderBlockCollection({
        generateBlockIds: generateBlockIdsSpy,
        minOccurs: 3,
        onChange: changeSpy,
        value,
    });

    // fillArrays runs automatically and generates IDs
    await act(async() => {
        await new Promise((resolve) => setTimeout(resolve, 0));
    });

    // Verify IDs were generated before onChange was called
    expect(generateBlockIdsSpy).toHaveBeenCalledWith(3);
    expect(changeSpy).toHaveBeenCalledWith([
        {type: 'editor', _id: 'id1'},
        {type: 'editor', _id: 'id2'},
        {type: 'editor', _id: 'id3'},
    ]);

    // Observables should be synchronized with value
    expect(ref.current.expandedBlocks.length).toBe(3);
    expect(ref.current.selectedBlocks.length).toBe(3);
    expect(ref.current.generatedBlockIds.length).toBe(3);
});

test('Should generate new ID when pasting cut blocks', async() => {
    const existingId = 'existing123';
    const newId = 'newid789';
    clipboard.set('blocks', [{content: 'Clipboard', type: 'editor', _id: existingId}]);

    const generateBlockIdsSpy = jest.fn().mockReturnValue(Promise.resolve([newId]));

    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}];
    const {ref} = renderBlockCollection({
        generateBlockIds: generateBlockIdsSpy,
        onChange: changeSpy,
        value,
    });

    // Call the handler directly to properly await the async operation
    await act(async() => {
        await ref.current.handlePasteBlocks(1);
    });

    expect(generateBlockIdsSpy).toHaveBeenCalledWith(1);
    expect(changeSpy).toHaveBeenCalledWith([
        {content: 'Test 1', type: 'editor'},
        {content: 'Clipboard', type: 'editor', _id: newId},
    ]);
});

test('Should generate new ID when pasting copied blocks without ID', async() => {
    const mockId = 'newid456';
    clipboard.set('blocks', [{content: 'Clipboard', type: 'editor'}]);

    const generateBlockIdsSpy = jest.fn().mockReturnValue(Promise.resolve([mockId]));

    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}];
    const {user} = renderBlockCollection({
        generateBlockIds: generateBlockIdsSpy,
        onChange: changeSpy,
        value,
    });

    const pasteButtons = getButtonsByName('sulu_admin.paste_blocks');
    await user.click(pasteButtons[pasteButtons.length - 1]);

    // Wait for async operation
    await act(async() => {
        await new Promise((resolve) => setTimeout(resolve, 0));
    });

    expect(generateBlockIdsSpy).toHaveBeenCalledWith(1);
    expect(changeSpy).toHaveBeenCalledWith([
        {content: 'Test 1', type: 'editor'},
        {content: 'Clipboard', type: 'editor', _id: mockId},
    ]);
});

test('Should remove ID from clipboard when copying blocks', async() => {
    const clipboardSpy = jest.fn();
    clipboard.observe('blocks', clipboardSpy);

    const generateBlockIdsSpy = jest.fn();
    const value: any = observable([{content: 'Test 1', type: 'editor', _id: 'testid123'}]);
    const {user} = renderBlockCollection({
        generateBlockIds: generateBlockIdsSpy,
        onChange: jest.fn(),
        value,
    });

    await openBlockActions(user, 0);
    await user.click(getButtonByName('sulu_admin.copy'));

    expect(clipboardSpy).toHaveBeenCalledWith([
        {content: 'Test 1', type: 'editor'}, // _id should be removed
    ]);
});

test('Should remove ID from clipboard when cutting blocks', async() => {
    const clipboardSpy = jest.fn();
    clipboard.observe('blocks', clipboardSpy);

    const generateBlockIdsSpy = jest.fn();
    const value: any = observable([
        {content: 'Test 1', type: 'editor', _id: 'testid123'},
        {content: 'Test 2', type: 'editor'},
    ]);
    const changeSpy = jest.fn().mockImplementation((newValue) => {
        value.splice(0, value.length);
        value.push(...newValue);
    });
    const {user} = renderBlockCollection({
        generateBlockIds: generateBlockIdsSpy,
        onChange: changeSpy,
        value,
    });

    await openBlockActions(user, 0);
    await user.click(getButtonByName('sulu_admin.cut'));

    expect(clipboardSpy).toHaveBeenCalledWith([
        {content: 'Test 1', type: 'editor'}, // _id should be removed for cut blocks too
    ]);
});

test('Should pre-generate IDs for blocks without IDs when loaded from server', async() => {
    const mockId1 = '12345678';
    const mockId2 = '87654321';
    const generateBlockIdsSpy = jest.fn().mockReturnValue(Promise.resolve([mockId1, mockId2]));
    const changeSpy = jest.fn();

    const value = [
        {
            type: 'type1',
            content: 'Block 1',
        },
        {
            type: 'type2',
            content: 'Block 2',
        },
    ];

    renderBlockCollection({
        generateBlockIds: generateBlockIdsSpy,
        onChange: changeSpy,
        value,
    });

    // Wait for async ID generation
    await act(async() => {
        await new Promise((resolve) => setTimeout(resolve, 0));
    });

    expect(generateBlockIdsSpy).toHaveBeenCalledWith(2);
    expect(changeSpy).toHaveBeenCalledWith([
        {
            type: 'type1',
            content: 'Block 1',
            _id: mockId1,
        },
        {
            type: 'type2',
            content: 'Block 2',
            _id: mockId2,
        },
    ]);
});

test('Should not pre-generate IDs for blocks that already have IDs', async() => {
    const existingId1 = 'existing1';
    const existingId2 = 'existing2';
    const generateBlockIdsSpy = jest.fn();
    const changeSpy = jest.fn();

    const value = [
        {
            type: 'type1',
            content: 'Block 1',
            _id: existingId1,
        },
        {
            type: 'type2',
            content: 'Block 2',
            _id: existingId2,
        },
    ];

    renderBlockCollection({
        generateBlockIds: generateBlockIdsSpy,
        onChange: changeSpy,
        value,
    });

    // Wait for potential async operations
    await act(async() => {
        await new Promise((resolve) => setTimeout(resolve, 0));
    });

    // Should not generate IDs since all blocks already have them
    expect(generateBlockIdsSpy).not.toHaveBeenCalled();
    expect(changeSpy).not.toHaveBeenCalled();
});

test('Should pre-generate IDs only for blocks without IDs (mixed case)', async() => {
    const existingId = 'existing1';
    const newId = '12345678';
    const generateBlockIdsSpy = jest.fn().mockReturnValue(Promise.resolve([newId]));
    const changeSpy = jest.fn();

    const value = [
        {
            type: 'type1',
            content: 'Block 1',
            _id: existingId,
        },
        {
            type: 'type2',
            content: 'Block 2',
        },
    ];

    renderBlockCollection({
        generateBlockIds: generateBlockIdsSpy,
        onChange: changeSpy,
        value,
    });

    // Wait for async ID generation
    await act(async() => {
        await new Promise((resolve) => setTimeout(resolve, 0));
    });

    expect(generateBlockIdsSpy).toHaveBeenCalledWith(1);
    expect(changeSpy).toHaveBeenCalledWith([
        {
            type: 'type1',
            content: 'Block 1',
            _id: existingId,
        },
        {
            type: 'type2',
            content: 'Block 2',
            _id: newId,
        },
    ]);
});

test('Should not generate IDs when generateBlockIds is not provided', async() => {
    const changeSpy = jest.fn();

    renderBlockCollection({
        onChange: changeSpy,
        value: [
            {
                type: 'type1',
                content: 'Block 1',
            },
            {
                type: 'type2',
                content: 'Block 2',
            },
        ],
    });

    // Wait for potential async operations
    await act(async() => {
        await new Promise((resolve) => setTimeout(resolve, 0));
    });

    // Should not try to generate IDs or call onChange since generateBlockIds is not provided
    expect(changeSpy).not.toHaveBeenCalled();
});
