// @flow
import React from 'react';
import {observable} from 'mobx';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Item from '../Item';

let mockSingleMediaSelectionOverlayProps: Object = {};
let mockTextEditorProps: Object = {};

const mockReact = require('react');

jest.mock('sulu-media-bundle/containers/SingleMediaSelectionOverlay', () => jest.fn((props) => {
    mockSingleMediaSelectionOverlayProps = props;

    return mockReact.createElement(
        'div',
        {
            'data-open': String(props.open),
            'data-testid': 'single-media-selection-overlay',
        },
        props.open && mockReact.createElement(
            'button',
            {onClick: () => props.onConfirm({id: 8}), type: 'button'},
            'confirm-media'
        )
    );
}));

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('sulu-admin-bundle/containers/TextEditor', () => jest.fn((props) => {
    mockTextEditorProps = props;

    return mockReact.createElement('textarea', {
        'aria-label': 'text-editor',
        onChange: (event) => props.onChange(event.currentTarget.value),
        value: props.value || '',
    });
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

    expect(mockTextEditorProps.adapter).toEqual('ckeditor5');
    expect(mockTextEditorProps.locale.get()).toEqual('en');
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

    expect(cancelSpy).not.toHaveBeenCalled();
    await user.click(screen.getByRole('button', {name: 'sulu_admin.cancel'}));
    expect(cancelSpy).toHaveBeenCalledWith('page', 5);
});

test('Reset the current field when the edit form is closed', async() => {
    const user = userEvent.setup();
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

    await user.clear(screen.getByLabelText('text-editor'));
    await user.type(screen.getByLabelText('text-editor'), 'Changed description');
    await user.clear(screen.getByDisplayValue('Edited title'));
    await user.type(screen.getByRole('textbox', {name: ''}), 'Changed title');

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

    expect(screen.getByLabelText('text-editor')).toHaveValue('Description');
    expect(screen.getByDisplayValue('Title')).toBeInTheDocument();
});

test('Reset the current field when the title or description props change', async() => {
    const user = userEvent.setup();
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

    await user.clear(screen.getByLabelText('text-editor'));
    await user.type(screen.getByLabelText('text-editor'), 'Changed description');
    await user.clear(screen.getByDisplayValue('Edited title'));
    await user.type(screen.getByRole('textbox', {name: ''}), 'Changed title');

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

    expect(screen.getByLabelText('text-editor')).toHaveValue('Description');
    expect(screen.getByDisplayValue('Title')).toBeInTheDocument();
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

    await user.clear(screen.getByLabelText('text-editor'));
    await user.type(screen.getByLabelText('text-editor'), 'Edited description');
    await user.clear(screen.getByDisplayValue('Title'));
    await user.type(screen.getByRole('textbox', {name: ''}), 'Edited title');

    expect(mockSingleMediaSelectionOverlayProps.open).toEqual(false);
    await user.click(screen.getByRole('button', {name: 'su-pen'}));
    expect(mockSingleMediaSelectionOverlayProps.open).toEqual(true);
    await user.click(screen.getByRole('button', {name: 'confirm-media'}));
    expect(mockSingleMediaSelectionOverlayProps.open).toEqual(false);

    expect(applySpy).not.toHaveBeenCalled();
    await user.click(screen.getByRole('button', {name: 'sulu_admin.apply'}));
    expect(applySpy).toHaveBeenCalledWith({
        description: 'Edited description',
        id: 5,
        mediaId: 8,
        title: 'Edited title',
        type: 'page',
    });
});

test('Resetting the item while editing should call the onApply callback with only id and type', async() => {
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

    expect(applySpy).toHaveBeenCalledWith({
        id: 5,
        type: 'page',
    });
});
