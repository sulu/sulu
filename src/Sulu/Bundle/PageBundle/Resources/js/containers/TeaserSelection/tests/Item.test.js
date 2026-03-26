// @flow
import React from 'react';
import {observable} from 'mobx';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {SingleMediaSelectionOverlay} from 'sulu-media-bundle/containers';
import {TextEditor} from 'sulu-admin-bundle/containers';
import Item from '../Item';

jest.mock('sulu-media-bundle/containers/SingleMediaSelectionOverlay', () => jest.fn(() => null));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/containers/TextEditor', () => jest.fn(function TextEditorMock({onChange, value}) {
    function handleChange(event) {
        if (onChange) {
            onChange(event.target.value);
        }
    }

    return (
        <textarea
            onChange={onChange ? handleChange : undefined}
            value={value}
        />
    );
}));

jest.mock('../registries/teaserProviderRegistry', () => ({
    keys: ['pages', 'articles'],
    get: jest.fn((key) => {
        switch (key) {
            case 'page':
                return {title: 'Page'};
        }
    }),
}));

function getLatestTextEditorProps() {
    const calls = (TextEditor: any).mock.calls;

    return calls[calls.length - 1][0];
}

function getLatestSingleMediaSelectionOverlayProps() {
    const calls = (SingleMediaSelectionOverlay: any).mock.calls;

    return calls[calls.length - 1][0];
}

beforeEach(() => {
    jest.clearAllMocks();
    Item.mediaUrl = undefined;
});

test('Render Item with data but without image', () => {
    Item.mediaUrl = '/admin/media/:id';

    const {asFragment} = render(
        <Item
            description="<p>Description</p>"
            edited={false}
            editing={false}
            id={5}
            locale={observable.box('en')}
            mediaId={undefined}
            onApply={jest.fn()}
            onCancel={jest.fn()}
            title="Title"
            type="page"
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render Item with data and image', () => {
    Item.mediaUrl = '/admin/image/:id';

    const {asFragment} = render(
        <Item
            description="<p>Description</p>"
            edited={true}
            editing={false}
            id={5}
            locale={observable.box('en')}
            mediaId={2}
            onApply={jest.fn()}
            onCancel={jest.fn()}
            title="Title"
            type="page"
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render Item without data', () => {
    const {asFragment} = render(
        <Item
            description={undefined}
            edited={false}
            editing={false}
            id={5}
            locale={observable.box('en')}
            mediaId={undefined}
            onApply={jest.fn()}
            onCancel={jest.fn()}
            title={undefined}
            type="page"
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render Item with data as form', () => {
    Item.mediaUrl = '/admin/image/:id';

    const {asFragment} = render(
        <Item
            description="Description"
            edited={false}
            editing={true}
            id={5}
            locale={observable.box('en')}
            mediaId={undefined}
            onApply={jest.fn()}
            onCancel={jest.fn()}
            title="Title"
            type="page"
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Pass correct props to text editor', () => {
    render(
        <Item
            description="Description"
            edited={false}
            editing={true}
            id={5}
            locale={observable.box('en')}
            mediaId={undefined}
            onApply={jest.fn()}
            onCancel={jest.fn()}
            title="Title"
            type="page"
        />
    );

    expect(getLatestTextEditorProps().adapter).toEqual('ckeditor5');
    expect(getLatestTextEditorProps().locale.get()).toEqual('en');
});

test('Cancelling the item while editing should call the onClose callback', async() => {
    const user = userEvent.setup();
    const cancelSpy = jest.fn();

    render(
        <Item
            description="Description"
            edited={false}
            editing={true}
            id={5}
            locale={observable.box('en')}
            mediaId={undefined}
            onApply={jest.fn()}
            onCancel={cancelSpy}
            title="Title"
            type="page"
        />
    );

    expect(cancelSpy).not.toBeCalled();
    await user.click(screen.getByRole('button', {name: 'sulu_admin.cancel'}));
    expect(cancelSpy).toBeCalledWith('page', 5);
});

test('Reset the current field when the edit form is closed', () => {
    const {rerender} = render(
        <Item
            description="Edited description"
            edited={false}
            editing={true}
            id={5}
            locale={observable.box('en')}
            mediaId={undefined}
            onApply={jest.fn()}
            onCancel={jest.fn()}
            title="Edited title"
            type="page"
        />
    );

    getLatestTextEditorProps().onChange('Edited description');
    expect(screen.getByDisplayValue('Edited title')).toBeInTheDocument();

    rerender(
        <Item
            description="Description"
            edited={false}
            editing={false}
            id={5}
            locale={observable.box('en')}
            mediaId={undefined}
            onApply={jest.fn()}
            onCancel={jest.fn()}
            title="Title"
            type="page"
        />
    );
    rerender(
        <Item
            description="Description"
            edited={false}
            editing={true}
            id={5}
            locale={observable.box('en')}
            mediaId={undefined}
            onApply={jest.fn()}
            onCancel={jest.fn()}
            title="Title"
            type="page"
        />
    );

    expect(screen.getByDisplayValue('Description')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Title')).toBeInTheDocument();
});

test('Reset the current field when the title or description props change', () => {
    const {rerender} = render(
        <Item
            description="Edited description"
            edited={false}
            editing={true}
            id={5}
            locale={observable.box('en')}
            mediaId={undefined}
            onApply={jest.fn()}
            onCancel={jest.fn()}
            title="Edited title"
            type="page"
        />
    );

    getLatestTextEditorProps().onChange('Edited description');
    expect(screen.getByDisplayValue('Edited title')).toBeInTheDocument();

    rerender(
        <Item
            description="Description"
            edited={false}
            editing={true}
            id={5}
            locale={observable.box('en')}
            mediaId={undefined}
            onApply={jest.fn()}
            onCancel={jest.fn()}
            title="Title"
            type="page"
        />
    );

    expect(screen.getByDisplayValue('Description')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Title')).toBeInTheDocument();
});

test('Applying the item while editing should call the onApply callback with the current data', async() => {
    const user = userEvent.setup();
    const applySpy = jest.fn();
    Item.mediaUrl = '/admin/image/:id';

    render(
        <Item
            description="Description"
            edited={false}
            editing={true}
            id={5}
            locale={observable.box('en')}
            mediaId={5}
            onApply={applySpy}
            onCancel={jest.fn()}
            title="Title"
            type="page"
        />
    );

    getLatestTextEditorProps().onChange('Edited description');
    const titleInput = screen.getByDisplayValue('Title');
    await user.clear(titleInput);
    await user.type(titleInput, 'Edited title');

    expect(getLatestSingleMediaSelectionOverlayProps().open).toEqual(false);
    const mediaButton = screen.getByRole('button', {name: /su-pen/i});
    await user.click(mediaButton);
    expect(getLatestSingleMediaSelectionOverlayProps().open).toEqual(true);
    getLatestSingleMediaSelectionOverlayProps().onConfirm({id: 8});
    expect(getLatestSingleMediaSelectionOverlayProps().open).toEqual(false);

    expect(applySpy).not.toBeCalled();
    await user.click(screen.getByRole('button', {name: 'sulu_admin.apply'}));
    expect(applySpy).toBeCalledWith({
        description: 'Edited description',
        id: 5,
        mediaId: 8,
        title: 'Edited title',
        type: 'page',
    });
});

test('Applying the item while editing should call the onApply callback with the current data', async() => {
    const user = userEvent.setup();
    const applySpy = jest.fn();

    render(
        <Item
            description="Description"
            edited={false}
            editing={true}
            id={5}
            locale={observable.box('en')}
            mediaId={5}
            onApply={applySpy}
            onCancel={jest.fn()}
            title="Title"
            type="page"
        />
    );

    await user.click(screen.getByRole('button', {name: 'sulu_admin.reset'}));
    expect(applySpy).toBeCalledWith({
        id: 5,
        type: 'page',
    });
});
