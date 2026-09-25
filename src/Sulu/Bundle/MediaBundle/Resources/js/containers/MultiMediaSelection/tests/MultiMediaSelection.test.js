// @flow
import React from 'react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import MultiSelectionStore from 'sulu-admin-bundle/stores/MultiSelectionStore';
import MultiMediaSelection from '../MultiMediaSelection';

let mockMultiSelectionStoreInstances: Array<Object> = [];

jest.mock('sulu-admin-bundle/utils/Translator');

function mockMultiMediaSelectionOverlay(props) {
    function handleConfirm() {
        props.onConfirm([{
            id: 1,
            mimeType: 'image/jpeg',
            title: 'Selected media',
            thumbnails: {},
        }]);
    }

    if (!props.open) {
        return null;
    }

    return React.createElement(
        'button',
        {
            onClick: handleConfirm,
            type: 'button',
        },
        'multi media selection overlay'
    );
}

jest.mock('../../MultiMediaSelectionOverlay', () => jest.fn(mockMultiMediaSelectionOverlay));

jest.mock('sulu-admin-bundle/stores/MultiSelectionStore', () => jest.fn(function() {
    this.items = [];
    this.loadItems = jest.fn();
    mockMultiSelectionStoreInstances.push(this);
}));

const MultiSelectionStoreMock = (MultiSelectionStore: any);

function getLatestMultiSelectionStore() {
    const store = mockMultiSelectionStoreInstances[mockMultiSelectionStoreInstances.length - 1];

    if (!store) {
        throw new Error('Expected a multi selection store to be created');
    }

    return store;
}

function mockMultiSelectionStoreOnce(implementation) {
    MultiSelectionStoreMock.mockImplementationOnce(function(...args) {
        implementation.apply(this, args);
        mockMultiSelectionStoreInstances.push(this);
    });
}

beforeEach(() => {
    mockMultiSelectionStoreInstances = [];
});

test('Render a MultiMediaSelection field', () => {
    // $FlowFixMe
    mockMultiSelectionStoreOnce(function() {
        this.items = [
            {
                id: 1,
                title: 'Media 1',
                thumbnails: {
                    'sulu-25x25': 'http://lorempixel.com/25/25',
                },
            },
            {
                id: 2,
                title: 'Media 2',
                thumbnails: {
                    'sulu-25x25': 'http://lorempixel.com/25/25',
                },
            },
            {
                id: 3,
                title: 'Media 3',
                thumbnails: {
                    'sulu-25x25': 'http://lorempixel.com/25/25',
                },
            },
        ];
    });

    const {container} = render(
        <MultiMediaSelection locale={observable.box('en')} onChange={jest.fn()} />
    );

    expect(container).toMatchSnapshot();
});

test('Render a MultiMediaSelection field with display options', () => {
    // $FlowFixMe
    mockMultiSelectionStoreOnce(function() {
        this.items = [
            {
                id: 1,
                title: 'Media 1',
                thumbnails: {
                    'sulu-25x25': 'http://lorempixel.com/25/25',
                },
            },
        ];
    });

    const {container} = render(
        <MultiMediaSelection
            displayOptions={['top', 'left', 'right', 'bottom']}
            locale={observable.box('en')}
            onChange={jest.fn()}
        />
    );

    expect(container).toMatchSnapshot();
});

test('Render a MultiMediaSelection field with display options and selected icon', () => {
    // $FlowFixMe
    mockMultiSelectionStoreOnce(function() {
        this.items = [
            {
                id: 1,
                title: 'Media 1',
                thumbnails: {
                    'sulu-25x25': 'http://lorempixel.com/25/25',
                },
            },
        ];
    });

    const {container} = render(
        <MultiMediaSelection
            displayOptions={['top', 'left', 'right', 'bottom']}
            locale={observable.box('en')}
            onChange={jest.fn()}
            value={{displayOption: 'left', ids: []}}
        />
    );

    expect(container).toMatchSnapshot();
});

test('Render a MultiMediaSelection field without thumbnails with MimeTypeIndicator', () => {
    // $FlowFixMe
    mockMultiSelectionStoreOnce(function() {
        this.items = [
            {
                id: 1,
                title: 'Media 1',
                mimeType: 'application/json',
            },
            {
                id: 2,
                title: 'Media 2',
                mimeType: 'application/pdf',
            },
            {
                id: 3,
                title: 'Media 3',
                mimeType: 'application/vnd.ms-excel',
            },
        ];
    });

    const {container} = render(
        <MultiMediaSelection locale={observable.box('en')} onChange={jest.fn()} />
    );

    expect(container).toMatchSnapshot();
});

test('The MultiMediaSelection should have 3 child-items', () => {
    // $FlowFixMe
    mockMultiSelectionStoreOnce(function() {
        this.items = [
            {
                id: 1,
                title: 'Media 1',
                thumbnails: {
                    'sulu-25x25': 'http://lorempixel.com/25/25',
                },
            },
            {
                id: 2,
                title: 'Media 2',
                thumbnails: {
                    'sulu-25x25': 'http://lorempixel.com/25/25',
                },
            },
            {
                id: 3,
                title: 'Media 3',
                thumbnails: {
                    'sulu-25x25': 'http://lorempixel.com/25/25',
                },
            },
        ];
    });

    render(
        <MultiMediaSelection locale={observable.box('en')} onChange={jest.fn()} />
    );

    expect(screen.getAllByRole('button', {name: /Media/})).toHaveLength(3);
});

test('Clicking on the "add media" button should open up an overlay', async() => {
    const user = userEvent.setup();
    render(<MultiMediaSelection locale={observable.box('en')} onChange={jest.fn()} />);

    expect(screen.queryByRole('button', {name: 'multi media selection overlay'})).not.toBeInTheDocument();
    await user.click(screen.getByRole('button', {name: 'su-image'}));
    expect(screen.getByRole('button', {name: 'multi media selection overlay'})).toBeInTheDocument();
});

test('Should remove media from the selection store', async() => {
    const user = userEvent.setup();
    // $FlowFixMe
    mockMultiSelectionStoreOnce(function() {
        this.items = [
            {
                id: 1,
                mimeType: 'image/jpeg',
                title: 'Media 1',
                thumbnails: {},
            },
        ];
        this.removeById = jest.fn();
    });

    render(<MultiMediaSelection locale={observable.box('en')} onChange={jest.fn()} />);

    await user.click(screen.getByRole('button', {name: 'su-trash-alt'}));

    expect(getLatestMultiSelectionStore().removeById).toHaveBeenCalledWith(1);
});

test('Should render media without sortable drag handles if sorting is disabled', () => {
    // $FlowFixMe
    mockMultiSelectionStoreOnce(function() {
        this.items = [
            {
                id: 1,
                mimeType: 'image/jpeg',
                title: 'Media 1',
                thumbnails: {},
            },
        ];
    });

    render(
        <MultiMediaSelection
            locale={observable.box('en')}
            onChange={jest.fn()}
            sortable={false}
        />
    );

    expect(screen.getByText('Media 1')).toBeInTheDocument();
    expect(screen.queryByLabelText('su-more')).not.toBeInTheDocument();
});

test('Should add the selected medias to the selection store on confirm', async() => {
    const user = userEvent.setup();
    // $FlowFixMe
    mockMultiSelectionStoreOnce(function() {
        this.items = [];
        this.set = jest.fn();
    });

    render(<MultiMediaSelection locale={observable.box('en')} onChange={jest.fn()} />);

    await user.click(screen.getByRole('button', {name: 'su-image'}));
    await user.click(screen.getByRole('button', {name: 'multi media selection overlay'}));

    expect(getLatestMultiSelectionStore().set).toHaveBeenCalledWith([
        {
            id: 1,
            mimeType: 'image/jpeg',
            title: 'Selected media',
            thumbnails: {},
        },
    ]);
    expect(screen.queryByRole('button', {name: 'multi media selection overlay'})).not.toBeInTheDocument();
});

test('Should call the onChange handler if selection store changes', () => {
    // $FlowFixMe
    mockMultiSelectionStoreOnce(function(resourceKey, selectedIds) {
        this.loadItems = jest.fn();
        mockExtendObservable(this, {
            items: selectedIds.map((id) => {
                return {id, mimeType: 'image/jpeg', thumbnails: {}};
            }),
        });
    });

    const changeSpy = jest.fn();

    render(
        <MultiMediaSelection
            locale={observable.box('en')}
            onChange={changeSpy}
            value={{displayOption: undefined, ids: [55]}}
        />
    );

    getLatestMultiSelectionStore().items.push({id: 99, mimeType: 'image/jpeg', thumbnails: {}});
    expect(changeSpy).toHaveBeenCalledWith({ids: [55, 99]});

    getLatestMultiSelectionStore().items.splice(0, 1);
    expect(changeSpy).toHaveBeenCalledWith({ids: [99]});
});

test('Should call the onChange handler if the displayOption changes', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    render(
        <MultiMediaSelection
            displayOptions={['left']}
            locale={observable.box('en')}
            onChange={changeSpy}
            value={{displayOption: undefined, ids: [55]}}
        />
    );

    await user.click(screen.getByRole('button', {name: 'su-display-default su-angle-down'}));
    await user.click(screen.getByRole('button', {name: /sulu_media\.left/}));

    expect(changeSpy).toHaveBeenCalledWith({displayOption: 'left', ids: [55]});
});

test('Should not call the onChange callback if the component props change', () => {
    const changeSpy = jest.fn();

    const {rerender} = render(
        <MultiMediaSelection
            locale={observable.box('en')}
            onChange={changeSpy}
            value={{displayOption: undefined, ids: [55]}}
        />
    );

    rerender(
        <MultiMediaSelection
            disabled={true}
            locale={observable.box('en')}
            onChange={changeSpy}
            value={{displayOption: undefined, ids: [55]}}
        />
    );
    expect(changeSpy).not.toHaveBeenCalled();
});

test('Should not call onChange callback if an unrelated observable that is accessed in the callback changes', () => {
    // $FlowFixMe
    mockMultiSelectionStoreOnce(function(resourceKey, selectedIds) {
        this.loadItems = jest.fn();
        mockExtendObservable(this, {
            items: selectedIds.map((id) => {
                return {id, mimeType: 'image/jpeg', thumbnails: {}};
            }),
        });
    });

    const unrelatedObservable = observable.box(22);
    const changeSpy = jest.fn(() => {
        jest.fn()(unrelatedObservable.get());
    });

    render(
        <MultiMediaSelection
            locale={observable.box('en')}
            onChange={changeSpy}
            value={{displayOption: undefined, ids: [55]}}
        />
    );

    // change callback should be called when item of the store mock changes
    getLatestMultiSelectionStore().items.push({id: 99, mimeType: 'image/jpeg', thumbnails: {}});
    expect(changeSpy).toHaveBeenCalledWith({ids: [55, 99]});
    expect(changeSpy).toHaveBeenCalledTimes(1);

    // change callback should not be called when the unrelated observable changes
    unrelatedObservable.set(55);
    expect(changeSpy).toHaveBeenCalledTimes(1);
});

test('Should call the onItemClick handler if an item is clicked', async() => {
    const user = userEvent.setup();
    // $FlowFixMe
    mockMultiSelectionStoreOnce(function(resourceKey, selectedIds) {
        mockExtendObservable(this, {
            items: selectedIds.map((id) => {
                return {id, mimeType: 'image/jpeg', thumbnails: {}, title: `Media ${selectedIds.indexOf(id) + 1}`};
            }),
        });
    });

    const itemClickSpy = jest.fn();

    render(
        <MultiMediaSelection
            locale={observable.box('en')}
            onChange={jest.fn()}
            onItemClick={itemClickSpy}
            value={{displayOption: undefined, ids: [55, 99]}}
        />
    );

    await user.click(screen.getByText('Media 1'));
    expect(itemClickSpy).toHaveBeenLastCalledWith(55, expect.objectContaining({id: 55, mimeType: 'image/jpeg'}));

    await user.click(screen.getByText('Media 2'));
    expect(itemClickSpy).toHaveBeenLastCalledWith(99, expect.objectContaining({id: 99, mimeType: 'image/jpeg'}));
});

test('Should disable the selection if the selection is disabled', () => {
    // $FlowFixMe
    mockMultiSelectionStoreOnce(function() {
        this.items = [
            {
                id: 1,
                mimeType: 'image/jpeg',
                title: 'Media 1',
                thumbnails: {},
            },
        ];
    });

    render(
        <MultiMediaSelection
            disabled={true}
            locale={observable.box('en')}
            onChange={jest.fn()}
        />
    );

    expect(screen.getByRole('button', {name: 'su-image'})).toBeDisabled();
    expect(screen.getByText('Media 1')).toBeInTheDocument();
});
