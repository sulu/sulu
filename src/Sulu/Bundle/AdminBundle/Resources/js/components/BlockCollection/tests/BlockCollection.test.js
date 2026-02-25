/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {act, render, waitFor} from '@testing-library/react';
import BlockCollection from '../BlockCollection';
import SortableBlockList from '../SortableBlockList';
import Button from '../../Button';
import clipboard from '../../../utils/clipboard/clipboard';
import findMockCallArg from '../../../utils/TestHelper/findMockCallArg';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../SortableBlockList', () => jest.fn(() => <div data-testid="sortable-block-list" />));
jest.mock('../../Button', () => jest.fn(({children}) => <button type="button">{children}</button>));
jest.mock('../../BlockToolbar', () => jest.fn(() => <div data-testid="block-toolbar" />));
jest.mock('../../Icon', () => jest.fn(() => <span data-testid="icon" />));
jest.mock('../../Sticky', () => jest.fn(({children}) => (
    <div data-testid="sticky">{typeof children === 'function' ? children(true) : children}</div>
)));

const SortableBlockListMock = SortableBlockList;
const ButtonMock = Button;

const baseProps = {
    defaultType: 'editor',
    onChange: jest.fn(),
    renderBlockContent: jest.fn(() => null),
    value: [{content: 'Test 1', type: 'editor'}, {content: 'Test 2', type: 'editor'}],
};

const getLastSortableBlockListProps = () => getLatestMockProps(SortableBlockListMock);

const getAddButtonProps = () => findMockCallArg(ButtonMock, ([props]) => props.icon === 'su-plus');

const getPasteButtonProps = () => findMockCallArg(ButtonMock, ([props]) => props.icon === 'su-copy');

beforeEach(() => {
    jest.clearAllMocks();
    BlockCollection.idCounter = 0;
    clipboard.set('blocks', undefined);
});

test('renders a fully filled block list', () => {
    const {asFragment} = render(
        <BlockCollection
            {...baseProps}
            icons={[[], ['su-eye']]}
            maxOccurs={3}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('renders in static mode when movable is false', () => {
    render(
        <BlockCollection
            {...baseProps}
            collapsable={false}
            movable={false}
        />
    );

    expect(getLastSortableBlockListProps().mode).toBe('static');
});

test('disables add button when block collection is disabled', () => {
    render(
        <BlockCollection
            {...baseProps}
            disabled={true}
        />
    );

    expect(getAddButtonProps().disabled).toBe(true);
});

test('disables add button when maxOccurs is reached', () => {
    render(
        <BlockCollection
            {...baseProps}
            maxOccurs={2}
        />
    );

    expect(getAddButtonProps().disabled).toBe(true);
});

test('renders add button text from addButtonText prop', () => {
    render(
        <BlockCollection
            {...baseProps}
            addButtonText="custom-add-button-text"
        />
    );

    expect(getAddButtonProps().children).toBe('custom-add-button-text');
});

test('renders paste button when clipboard contains blocks', async() => {
    clipboard.set('blocks', [{content: 'Test 3', type: 'editor'}]);

    render(<BlockCollection {...baseProps} />);

    await waitFor(() => expect(() => getPasteButtonProps()).not.toThrow());
    expect(getPasteButtonProps().children).toBe('sulu_admin.paste_blocks');
});

test('renders paste button text from pasteButtonText prop', async() => {
    clipboard.set('blocks', [{content: 'Test 3', type: 'editor'}]);

    render(
        <BlockCollection
            {...baseProps}
            pasteButtonText="custom-paste-button-text"
        />
    );

    await waitFor(() => expect(() => getPasteButtonProps()).not.toThrow());
    expect(getPasteButtonProps().children).toBe('custom-paste-button-text');
});

test('fills blocks up to minOccurs', async() => {
    const onChange = jest.fn();

    render(
        <BlockCollection
            defaultType="editor"
            minOccurs={2}
            onChange={onChange}
            renderBlockContent={jest.fn(() => null)}
            value={[]}
        />
    );

    await waitFor(() => expect(onChange).toHaveBeenCalled());
    const newValue = getLatestMockProps(onChange);
    expect(newValue).toHaveLength(2);
    expect(newValue[0]).not.toBe(newValue[1]);
    expect(newValue[0].type).toBe('editor');
    expect(newValue[1].type).toBe('editor');
});

test('generates IDs for minOccurs blocks when generateBlockIds is provided', async() => {
    const onChange = jest.fn();
    const generateBlockIds = jest.fn().mockResolvedValue(['id1', 'id2', 'id3']);

    render(
        <BlockCollection
            defaultType="editor"
            generateBlockIds={generateBlockIds}
            minOccurs={3}
            onChange={onChange}
            renderBlockContent={jest.fn(() => null)}
            value={[]}
        />
    );

    await waitFor(() => expect(generateBlockIds).toHaveBeenCalledWith(3));
    expect(onChange).toHaveBeenCalledWith([
        {type: 'editor', _id: 'id1'},
        {type: 'editor', _id: 'id2'},
        {type: 'editor', _id: 'id3'},
    ]);
});

test('calls onChange when type changes through list adapter callback', () => {
    const onChange = jest.fn();

    render(
        <BlockCollection
            defaultType="editor"
            onChange={onChange}
            renderBlockContent={jest.fn(() => null)}
            types={{type1: 'Type 1', type2: 'Type 2'}}
            value={[
                {type: 'type1', content: 'Test 1'},
                {type: 'type2', content: 'Test 2'},
            ]}
        />
    );

    getLastSortableBlockListProps().onTypeChange('type2', 0);

    expect(onChange).toHaveBeenCalledWith([
        {type: 'type2', content: 'Test 1'},
        {type: 'type2', content: 'Test 2'},
    ]);
});

test('adds a block through exposed method', async() => {
    const onChange = jest.fn();
    const ref = React.createRef();

    render(
        <BlockCollection
            defaultType="editor"
            onChange={onChange}
            ref={ref}
            renderBlockContent={jest.fn(() => null)}
            value={[{type: 'editor', content: 'A'}]}
        />
    );

    await act(async() => {
        await ref.current.handleAddBlock(1);
    });

    expect(onChange).toHaveBeenCalledWith([
        {type: 'editor', content: 'A'},
        {type: 'editor'},
    ]);
});

test('sorts blocks through onSortEnd callback', () => {
    const onChange = jest.fn();
    const onSortEnd = jest.fn();

    render(
        <BlockCollection
            defaultType="editor"
            onChange={onChange}
            onSortEnd={onSortEnd}
            renderBlockContent={jest.fn(() => null)}
            value={[
                {type: 'editor', content: 'A'},
                {type: 'editor', content: 'B'},
            ]}
        />
    );

    getLastSortableBlockListProps().onSortEnd({oldIndex: 0, newIndex: 1});

    expect(onChange).toHaveBeenCalledWith([
        {type: 'editor', content: 'B'},
        {type: 'editor', content: 'A'},
    ]);
    expect(onSortEnd).toHaveBeenCalledWith(0, 1);
});
