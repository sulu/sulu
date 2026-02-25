// @flow
import React from 'react';
import {observable} from 'mobx';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import Item from '../Item';

jest.mock('sulu-media-bundle/components', () => ({
    MimeTypeIndicator: jest.fn(() => null),
}));

jest.mock('sulu-media-bundle/containers', () => ({
    SingleMediaSelectionOverlay: jest.fn(() => null),
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/containers', () => ({
    TextEditor: jest.fn(({value}) => (<textarea readOnly={true} value={value || ''} />)),
}));

jest.mock('sulu-admin-bundle/components', () => ({
    Button: jest.fn(({children, onClick}) => (
        <button onClick={onClick} type="button">{children}</button>
    )),
    Icon: jest.fn(({name}) => <span>{name}</span>),
    Input: jest.fn(({onChange, value}) => (
        <input onChange={onChange} type="text" value={value || ''} />
    )),
}));

jest.mock('../registries/teaserProviderRegistry', () => ({
    keys: ['pages', 'articles'],
    get: jest.fn((key) => {
        switch (key) {
            case 'page':
                return {title: 'Page'};
            default:
                return undefined;
        }
    }),
}));

const singleMediaSelectionOverlayComponent = ((jest.requireMock('sulu-media-bundle/containers'): any)
    .SingleMediaSelectionOverlay: {
        mock: {calls: Array<[Object]>},
        ...
    });
const textEditorComponent = ((jest.requireMock('sulu-admin-bundle/containers'): any).TextEditor: {
    mock: {calls: Array<[Object]>},
    ...
});
const inputComponent = ((jest.requireMock('sulu-admin-bundle/components'): any).Input: {
    mock: {calls: Array<[Object]>},
    ...
});

const createItemProps = (overrides: Object = {}) => ({
    description: 'Description',
    edited: false,
    editing: false,
    id: 5,
    locale: observable.box('en'),
    mediaId: undefined,
    onApply: jest.fn(),
    onCancel: jest.fn(),
    title: 'Title',
    type: 'page',
    ...overrides,
});

beforeEach(() => {
    jest.clearAllMocks();
    Item.mediaUrl = '/admin/image/:id';
});

test('Render Item with data but without image', () => {
    Item.mediaUrl = '/admin/media/:id';

    const {asFragment} = render(
        <Item
            {...createItemProps({
                description: '<p>Description</p>',
                mediaId: undefined,
            })}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render Item with data and image', () => {
    Item.mediaUrl = '/admin/image/:id';

    const {asFragment} = render(
        <Item
            {...createItemProps({
                description: '<p>Description</p>',
                edited: true,
                mediaId: 2,
            })}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render Item without data', () => {
    const {asFragment} = render(
        <Item
            {...createItemProps({
                description: undefined,
                mediaId: undefined,
                title: undefined,
            })}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render Item with data as form', () => {
    const {asFragment} = render(<Item {...createItemProps({editing: true})} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Pass correct props to text editor', () => {
    render(<Item {...createItemProps({editing: true})} />);

    expect(getLatestMockProps(textEditorComponent).adapter).toEqual('ckeditor5');
    expect(getLatestMockProps(textEditorComponent).locale.get()).toEqual('en');
});

test('Cancelling the item while editing should call the onClose callback', async() => {
    const cancelSpy = jest.fn();
    render(<Item {...createItemProps({editing: true, onCancel: cancelSpy})} />);

    expect(cancelSpy).not.toBeCalled();
    await userEvent.click(screen.getByRole('button', {name: 'sulu_admin.cancel'}));
    expect(cancelSpy).toBeCalledWith('page', 5);
});

test('Reset the current field when the edit form is closed', () => {
    const locale = observable.box('en');
    const {rerender} = render(
        <Item
            {...createItemProps({
                description: 'Edited description',
                editing: true,
                locale,
                title: 'Edited title',
            })}
        />
    );

    getLatestMockProps(textEditorComponent).onChange('Other description');
    getLatestMockProps(inputComponent).onChange('Other title');

    rerender(
        <Item
            {...createItemProps({
                description: 'Description',
                editing: false,
                locale,
                title: 'Title',
            })}
        />
    );
    rerender(
        <Item
            {...createItemProps({
                description: 'Description',
                editing: true,
                locale,
                title: 'Title',
            })}
        />
    );

    expect(getLatestMockProps(textEditorComponent).value).toEqual('Description');
    expect(getLatestMockProps(inputComponent).value).toEqual('Title');
});

test('Reset the current field when the title or description props change', () => {
    const locale = observable.box('en');
    const {rerender} = render(
        <Item
            {...createItemProps({
                description: 'Edited description',
                editing: true,
                locale,
                title: 'Edited title',
            })}
        />
    );

    getLatestMockProps(textEditorComponent).onChange('Other description');
    getLatestMockProps(inputComponent).onChange('Other title');

    rerender(
        <Item
            {...createItemProps({
                description: 'Description',
                editing: true,
                locale,
                title: 'Title',
            })}
        />
    );

    expect(getLatestMockProps(textEditorComponent).value).toEqual('Description');
    expect(getLatestMockProps(inputComponent).value).toEqual('Title');
});

test('Applying the item while editing should call the onApply callback with the current data', async() => {
    const applySpy = jest.fn();
    render(
        <Item
            {...createItemProps({
                editing: true,
                mediaId: 5,
                onApply: applySpy,
            })}
        />
    );

    getLatestMockProps(textEditorComponent).onChange('Edited description');
    getLatestMockProps(inputComponent).onChange('Edited title');

    expect(getLatestMockProps(singleMediaSelectionOverlayComponent).open).toEqual(false);
    await userEvent.click(screen.getByRole('button', {name: 'su-pen'}));
    expect(getLatestMockProps(singleMediaSelectionOverlayComponent).open).toEqual(true);

    getLatestMockProps(singleMediaSelectionOverlayComponent).onConfirm({id: 8});
    expect(getLatestMockProps(singleMediaSelectionOverlayComponent).open).toEqual(false);

    expect(applySpy).not.toBeCalled();
    await userEvent.click(screen.getByRole('button', {name: 'sulu_admin.apply'}));
    expect(applySpy).toBeCalledWith({
        description: 'Edited description',
        id: 5,
        mediaId: 8,
        title: 'Edited title',
        type: 'page',
    });
});

test('Resetting the item while editing should call the onApply callback with id and type', async() => {
    const applySpy = jest.fn();
    render(
        <Item
            {...createItemProps({
                editing: true,
                mediaId: 5,
                onApply: applySpy,
            })}
        />
    );

    await userEvent.click(screen.getByRole('button', {name: 'sulu_admin.reset'}));
    expect(applySpy).toBeCalledWith({
        id: 5,
        type: 'page',
    });
});
