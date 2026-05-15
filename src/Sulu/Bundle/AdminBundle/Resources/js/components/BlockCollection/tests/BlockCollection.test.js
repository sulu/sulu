// @flow
import {act, render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import {observable} from 'mobx';
import BlockCollection from '../BlockCollection';
import clipboard from '../../../utils/clipboard/clipboard';

let mockLastSortEnd;

jest.mock('react-sortable-hoc', () => {
    const React = require('react');

    return {
        SortableContainer: jest.fn().mockImplementation((Component) => function SortableContainerMock(props) {
            mockLastSortEnd = props.onSortEnd;

            return <Component {...props} />;
        }),
        SortableElement: jest.fn().mockImplementation((Component) => Component),
        SortableHandle: jest.fn().mockImplementation((Component) => Component),
    };
});

type RenderBlockCollectionResult = {
    container: HTMLElement,
    getCurrentProps: () => Object,
    rerenderBlockCollection: (nextProps: Object) => void,
    user: any,
};

function renderBlockCollection(props: Object = {}): RenderBlockCollectionResult {
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
        />
    );

    return {
        container,
        rerenderBlockCollection: (nextProps: Object) => {
            currentProps = {...currentProps, ...nextProps};

            rerender(
                <BlockCollection
                    {...currentProps}
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

    expect(changeSpy).toBeCalledWith([
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

    renderBlockCollection({
        minOccurs: 2,
        onChange: changeSpy,
        value,
    });

    expect(changeSpy).toBeCalledWith([
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

    expect(changeSpy).toBeCalledWith([
        expect.objectContaining({type: 'default'}),
        expect.objectContaining({type: 'editor'}),
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

    expect(changeSpy).toBeCalledWith([
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
    const {user} = renderBlockCollection({
        onChange: changeSpy,
        onSortEnd: sortEndSpy,
        value,
    });

    await user.click(getBlock(0));

    expect(isBlockExpanded(0)).toEqual(true);
    expect(isBlockExpanded(1)).toEqual(false);
    expect(isBlockExpanded(2)).toEqual(false);

    act(() => {
        mockLastSortEnd({newIndex: 2, oldIndex: 0});
    });
    expect(changeSpy).toBeCalledWith([
        expect.objectContaining({content: 'Test 2'}),
        expect.objectContaining({content: 'Test 3'}),
        expect.objectContaining({content: 'Test 1'}),
    ]);
    expect(sortEndSpy).toBeCalledWith(0, 2);

    expect(isBlockExpanded(0)).toEqual(false);
    expect(isBlockExpanded(1)).toEqual(false);
    expect(isBlockExpanded(2)).toEqual(true);
});

test('Should add a new block between existing blocks', async() => {
    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];
    const {user} = renderBlockCollection({
        onChange: changeSpy,
        value,
    });

    await user.click(getButtonsByName('sulu_admin.add_block')[0]);

    expect(changeSpy).toBeCalledWith([
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

    expect(changeSpy).toBeCalledWith([...value, {type: 'editor'}]);
});

test('Should not add a new block if the maximum has already been reached', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];

    renderBlockCollection({
        maxOccurs: 2,
        onChange: changeSpy,
        value,
    });

    await user.click(getButtonsByName('sulu_admin.add_block')[0]);

    expect(changeSpy).not.toBeCalled();
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

    expect(changeSpy).toBeCalledWith([
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

    expect(changeSpy).toBeCalledWith([...value, {content: 'Clipboard', type: 'editor'}]);
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

    expect(changeSpy).toBeCalledWith([...value, {content: 'Clipboard', type: 'editor'}]);
});

test('Should not paste a block if the maximum has already been reached', async() => {
    const user = userEvent.setup();
    clipboard.set('blocks', [{content: 'Clipboard', type: 'editor'}]);

    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];

    renderBlockCollection({
        maxOccurs: 2,
        onChange: changeSpy,
        value,
    });

    await user.click(getButtonsByName('sulu_admin.paste_blocks')[0]);

    expect(changeSpy).not.toBeCalled();
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

    expect(changeSpy).toBeCalledWith([
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

test('Should not duplicate a block if maxOccurs limit is reached', async() => {
    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];
    const {user} = renderBlockCollection({
        maxOccurs: 2,
        onChange: changeSpy,
        value,
    });

    await openBlockActions(user, 0);

    expect(queryButtonByName('sulu_admin.duplicate')).not.toBeInTheDocument();
    expect(changeSpy).not.toBeCalled();
});

test('Should not remove a block if minOccurs limit is reached', async() => {
    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];
    const {user} = renderBlockCollection({
        minOccurs: 2,
        onChange: changeSpy,
        value,
    });

    await openBlockActions(user, 0);

    expect(queryButtonByName('sulu_admin.delete')).not.toBeInTheDocument();
    expect(changeSpy).not.toBeCalled();
});

test('Should not cut a block if minOccurs limit is reached', async() => {
    const changeSpy = jest.fn();
    const value = [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}];
    const {user} = renderBlockCollection({
        minOccurs: 2,
        onChange: changeSpy,
        value,
    });

    await openBlockActions(user, 0);

    expect(queryButtonByName('sulu_admin.cut')).not.toBeInTheDocument();
    expect(changeSpy).not.toBeCalled();
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

    expect(changeSpy).toBeCalledWith([expect.objectContaining({content: 'Test 2'})]);
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

test('Should pass copy action that allows to cut an existing block into the clipboard', async() => {
    const clipboardSpy = jest.fn();
    const removeObserver = clipboard.observe('blocks', clipboardSpy);

    const value: any = observable([{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}]);
    const {user} = renderBlockCollection({
        value,
    });

    await openBlockActions(user, 0);
    expect(getButtonByName('sulu_admin.copy')).toBeInTheDocument();

    expect(clipboardSpy).not.toBeCalled();

    await user.click(getButtonByName('sulu_admin.copy'));

    expect(clipboardSpy).toBeCalledWith([value[0]]);

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

    expect(clipboardSpy).not.toBeCalled();

    await user.click(getButtonByName('sulu_admin.cut'));

    expect(clipboardSpy).toBeCalledWith([expect.objectContaining({content: 'Test 1'})]);
    expect(changeSpy).toBeCalledWith([expect.objectContaining({content: 'Test 2'})]);

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

test('Should keep rendered block state consistent after updating the value variable with fewer entries', async() => {
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
    await blockCollection.user.click(getBlock(0));

    expect(blockCollection.getCurrentProps().value.length).toBe(3);
    expect(getBlocks()).toHaveLength(3);
    expect(isBlockExpanded(0)).toBe(true);

    blockCollection.rerenderBlockCollection({
        value: [
            {
                type: 'type1',
                content: 'Test 1',
            },
        ],
    });

    expect(blockCollection.getCurrentProps().value.length).toBe(1);
    expect(getBlocks()).toHaveLength(1);
    expect(isBlockExpanded(0)).toBe(true);
});

test('Should keep rendered block state consistent after updating the value variable with more entries', async() => {
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
    await blockCollection.user.click(getBlock(0));

    expect(blockCollection.getCurrentProps().value.length).toBe(1);
    expect(getBlocks()).toHaveLength(1);
    expect(isBlockExpanded(0)).toBe(true);

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
    expect(getBlocks()).toHaveLength(3);
    expect(isBlockExpanded(0)).toBe(true);
});

test('Updating value with same length should keep rendered block state', async() => {
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
    await blockCollection.user.click(getBlock(0));

    expect(blockCollection.getCurrentProps().value.length).toBe(3);
    expect(getBlocks()).toHaveLength(3);
    expect(isBlockExpanded(0)).toBe(true);

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
    expect(getBlocks()).toHaveLength(3);
    expect(isBlockExpanded(0)).toBe(true);
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

    expect(clipboardSpy).toBeCalledWith(value);
    expect(changeSpy).not.toBeCalled();

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

    expect(clipboardSpy).not.toBeCalled();
    expect(changeSpy).toBeCalledWith([...value, ...value]);

    removeObserver();
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

    expect(clipboardSpy).toBeCalledWith(value);
    expect(changeSpy).toBeCalledWith([]);

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

    expect(clipboardSpy).not.toBeCalled();
    expect(changeSpy).toBeCalledWith([]);

    removeObserver();
});
