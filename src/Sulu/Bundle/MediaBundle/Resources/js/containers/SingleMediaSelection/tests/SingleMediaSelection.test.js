// @flow
import React from 'react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import SingleSelectionStore from 'sulu-admin-bundle/stores/SingleSelectionStore';
import SingleMediaSelection from '../SingleMediaSelection';

let mockSingleSelectionStoreInstances: Array<Object> = [];
let mockSingleMediaSelectionOverlayProps: Object = {};

const mockReact = require('react');

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('../../MediaEditOverlay', () => jest.fn((props) => {
    if (!props.open) {
        return null;
    }

    return mockReact.createElement(
        'button',
        {onClick: props.onConfirm, type: 'button'},
        `save media ${props.id} in ${props.locale.get()}`
    );
}));

jest.mock('../../SingleMediaSelectionOverlay', () => jest.fn((props) => {
    mockSingleMediaSelectionOverlayProps = props;

    return mockReact.createElement(
        mockReact.Fragment,
        null,
        'single media selection overlay',
        props.open && mockReact.createElement(
            'button',
            {
                onClick: () => props.onConfirm({
                    id: 22,
                    mimeType: 'image/jpeg',
                    thumbnails: {
                        'sulu-25x25': '/images/25x25/awesome.png',
                    },
                    title: 'test media',
                }),
                type: 'button',
            },
            'confirm overlay selection'
        )
    );
}));

jest.mock('sulu-admin-bundle/stores/SingleSelectionStore', () => jest.fn(function() {
    mockSingleSelectionStoreInstances.push(this);
}));

const SingleSelectionStoreMock = (SingleSelectionStore: any);

function getLatestSingleSelectionStore() {
    const store = mockSingleSelectionStoreInstances[mockSingleSelectionStoreInstances.length - 1];

    if (!store) {
        throw new Error('Expected a single selection store to be created');
    }

    return store;
}

function mockSingleSelectionStoreOnce(implementation) {
    SingleSelectionStoreMock.mockImplementationOnce(function(...args) {
        implementation.apply(this, args);
        mockSingleSelectionStoreInstances.push(this);
    });
}

beforeEach(() => {
    mockSingleSelectionStoreInstances = [];
});

test('Component should render without selected media', () => {
    const {container} = render(
        <SingleMediaSelection locale={observable.box('en')} onChange={jest.fn()} value={undefined} />
    );

    expect(SingleSelectionStore).toHaveBeenCalledWith('media', undefined, expect.anything());
    expect(container).toMatchSnapshot();
});

test('Component should render with display options', () => {
    const {container} = render(
        <SingleMediaSelection
            displayOptions={['top', 'bottom']}
            locale={observable.box('en')}
            onChange={jest.fn()}
            value={undefined}
        />
    );

    expect(container).toMatchSnapshot();
});

test('Component should render with display options and correctly selected icon', () => {
    const {container} = render(
        <SingleMediaSelection
            displayOptions={['top', 'bottom']}
            locale={observable.box('en')}
            onChange={jest.fn()}
            value={{displayOption: 'left', id: undefined}}
        />
    );

    expect(container).toMatchSnapshot();
});

test('Component should render with selected media', () => {
    // $FlowFixMe
    mockSingleSelectionStoreOnce(function() {
        this.item = {
            id: 33,
            title: 'test media',
            mimeType: 'image/jpeg',
            thumbnails: {
                'sulu-25x25': 'http://lorempixel.com/25/25',
            },
        };
    });

    const {container} = render(
        <SingleMediaSelection
            locale={observable.box('en')}
            onChange={jest.fn()}
            value={{displayOption: undefined, id: 33}}
        />
    );

    expect(SingleSelectionStore).toHaveBeenCalledWith('media', 33, expect.anything());
    expect(container).toMatchSnapshot();
});

test('Component should render with selected media without thumbnails with MimeTypeIndicator', () => {
    // $FlowFixMe
    mockSingleSelectionStoreOnce(function() {
        this.item = {
            id: 33,
            title: 'test media',
            mimeType: 'application/pdf',
        };
    });

    const {container} = render(
        <SingleMediaSelection
            locale={observable.box('en')}
            onChange={jest.fn()}
            value={{displayOption: undefined, id: 33}}
        />
    );

    expect(SingleSelectionStore).toHaveBeenCalledWith('media', 33, expect.anything());
    expect(container).toMatchSnapshot();
});

test('Component should pass className to SingleItemSelection', () => {
    render(
        <SingleMediaSelection
            className="test"
            locale={observable.box('en')}
            onChange={jest.fn()}
            value={undefined}
        />
    );

    expect(screen.getByText('sulu_media.select_media_singular').closest('.singleItemSelection')).toHaveClass('test');
});

test('Component should pass types to SingleMediaSelectionOverlay', () => {
    render(
        <SingleMediaSelection
            locale={observable.box('en')}
            onChange={jest.fn()}
            types={['image', 'video']}
            value={undefined}
        />
    );

    expect(mockSingleMediaSelectionOverlayProps.types).toEqual(['image', 'video']);
});

test('Click on media-button should open an overlay', async() => {
    const user = userEvent.setup();

    render(<SingleMediaSelection locale={observable.box('en')} onChange={jest.fn()} value={undefined} />);

    expect(mockSingleMediaSelectionOverlayProps.open).toEqual(false);

    await user.click(screen.getByRole('button', {name: 'su-image'}));

    expect(mockSingleMediaSelectionOverlayProps.open).toEqual(true);
});

test('Media that is selected in the overlay should be set to the selection store on confirm', async() => {
    const user = userEvent.setup();
    // $FlowFixMe
    mockSingleSelectionStoreOnce(function() {
        this.set = jest.fn();
    });

    render(<SingleMediaSelection locale={observable.box('en')} onChange={jest.fn()} value={undefined} />);

    await user.click(screen.getByRole('button', {name: 'su-image'}));
    await user.click(screen.getByRole('button', {name: 'confirm overlay selection'}));

    expect(getLatestSingleSelectionStore().set).toHaveBeenCalledWith(expect.objectContaining({
        id: 22,
        mimeType: 'image/jpeg',
        thumbnails: {
            'sulu-25x25': '/images/25x25/awesome.png',
        },
        title: 'test media',
    }));
});

test('Click on remove-button should clear the selection store', async() => {
    const user = userEvent.setup();
    // $FlowFixMe
    mockSingleSelectionStoreOnce(function() {
        this.item = {
            id: 33,
            title: 'test media',
            mimeType: 'image/jpeg',
            thumbnails: {
                'sulu-25x25': 'http://lorempixel.com/25/25',
            },
        };
        this.clear = jest.fn();
    });

    render(
        <SingleMediaSelection
            locale={observable.box('en')}
            onChange={jest.fn()}
            value={{displayOption: undefined, id: 33}}
        />
    );

    await user.click(screen.getByRole('button', {name: 'su-trash-alt'}));
    expect(getLatestSingleSelectionStore().clear).toHaveBeenCalled();
});

test('Should call the onChange handler if the displayOption changes', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    render(
        <SingleMediaSelection
            displayOptions={['left']}
            locale={observable.box('en')}
            onChange={changeSpy}
            value={undefined}
        />
    );

    await user.click(screen.getByRole('button', {name: 'su-display-default su-angle-down'}));
    await user.click(screen.getByRole('button', {name: /sulu_media\.left/}));

    expect(changeSpy).toHaveBeenCalledWith({displayOption: 'left', id: undefined});
});

test('Should call given onChange handler if value of selection store changes', () => {
    // $FlowFixMe
    mockSingleSelectionStoreOnce(function() {
        this.loadItem = jest.fn();
        mockExtendObservable(this, {
            item: undefined,
        });
    });

    const changeSpy = jest.fn();

    render(<SingleMediaSelection locale={observable.box('en')} onChange={changeSpy} value={undefined} />);

    expect(changeSpy).not.toHaveBeenCalled();
    getLatestSingleSelectionStore().item = {
        id: 77,
        title: 'test media',
        mimeType: 'image/jpeg',
        thumbnails: {},
    };
    expect(changeSpy).toHaveBeenCalledWith({id: 77}, getLatestSingleSelectionStore().item);
});

test('Should not call onChange callback if an unrelated observable that is accessed in the callback changes', () => {
    // $FlowFixMe
    mockSingleSelectionStoreOnce(function() {
        this.loadItem = jest.fn();
        mockExtendObservable(this, {
            item: undefined,
        });
    });

    const unrelatedObservable = observable.box(22);
    const changeSpy = jest.fn(() => {
        jest.fn()(unrelatedObservable.get());
    });

    render(<SingleMediaSelection locale={observable.box('en')} onChange={changeSpy} value={undefined} />);

    // change callback should be called when item of the store mock changes
    getLatestSingleSelectionStore().item = {id: 77, mimeType: 'image/jpeg', thumbnails: {}};
    expect(changeSpy).toHaveBeenCalledWith({id: 77}, getLatestSingleSelectionStore().item);
    expect(changeSpy).toHaveBeenCalledTimes(1);

    // change callback should not be called when the unrelated observable changes
    unrelatedObservable.set(55);
    expect(changeSpy).toHaveBeenCalledTimes(1);
});

test('Should not call the onChange callback if the component props change', () => {
    // $FlowFixMe
    mockSingleSelectionStoreOnce(function() {
        this.loadItem = jest.fn();
    });

    const changeSpy = jest.fn();

    const {rerender} = render(
        <SingleMediaSelection
            locale={observable.box('en')}
            onChange={changeSpy}
            value={{displayOption: undefined, id: 5}}
        />
    );

    rerender(
        <SingleMediaSelection
            disabled={true}
            locale={observable.box('en')}
            onChange={changeSpy}
            value={{displayOption: undefined, id: 5}}
        />
    );
    expect(changeSpy).not.toHaveBeenCalled();
});

test('Should not call the onItemClick callback if no item is available', async() => {
    const user = userEvent.setup();
    // $FlowFixMe
    mockSingleSelectionStoreOnce(function() {
        this.item = undefined;
    });

    const itemClickSpy = jest.fn();

    render(
        <SingleMediaSelection
            locale={observable.box('en')}
            onChange={jest.fn()}
            onItemClick={itemClickSpy}
            value={{displayOption: undefined, id: 5}}
        />
    );

    await user.click(screen.getByRole('button', {name: 'sulu_media.select_media_singular'}));
    expect(itemClickSpy).not.toHaveBeenCalled();
});

test('Should call the onItemClick callback if the item is clicked', async() => {
    const user = userEvent.setup();

    // $FlowFixMe
    mockSingleSelectionStoreOnce(function() {
        this.item = {id: 6, title: 'test media', mimeType: 'image/jpeg'};
    });

    const itemClickSpy = jest.fn();

    render(
        <SingleMediaSelection
            locale={observable.box('en')}
            onChange={jest.fn()}
            onItemClick={itemClickSpy}
            value={{displayOption: undefined, id: 5}}
        />
    );

    await user.click(screen.getByRole('button', {name: /test media/}));
    expect(itemClickSpy).toHaveBeenCalledWith(6, {id: 6, title: 'test media', mimeType: 'image/jpeg'});
});

test('Should not call the loadItem callback if the component props id change to same value', () => {
    // $FlowFixMe
    mockSingleSelectionStoreOnce(function() {
        this.loadItem = jest.fn();
    });

    const changeSpy = jest.fn();

    const {rerender} = render(
        <SingleMediaSelection
            locale={observable.box('en')}
            onChange={changeSpy}
            value={{displayOption: undefined, id: 5}}
        />
    );

    rerender(
        <SingleMediaSelection
            locale={observable.box('en')}
            onChange={changeSpy}
            value={{displayOption: undefined, id: 5}}
        />
    );
    expect(getLatestSingleSelectionStore().loadItem).not.toHaveBeenCalled();
});

test('Correct props should be passed to SingleItemSelection component', () => {
    render(
        <SingleMediaSelection
            disabled={true}
            locale={observable.box('en')}
            onChange={jest.fn()}
            valid={false}
            value={undefined}
        />
    );
    const singleItemSelection = screen.getByText('sulu_media.select_media_singular').closest('.singleItemSelection');

    expect(screen.getByRole('button', {name: 'su-image'})).toBeDisabled();
    expect(singleItemSelection).toHaveClass('disabled');
    expect(singleItemSelection).toHaveClass('error');
});

test('Set loading prop of SingleItemSelection component if SingleSelectionStore is loading', () => {
    // $FlowFixMe
    mockSingleSelectionStoreOnce(function() {
        mockExtendObservable(this, {
            loading: false,
        });
    });

    render(
        <SingleMediaSelection disabled={true} locale={observable.box('en')} onChange={jest.fn()} value={undefined} />
    );

    expect(screen.getByText('sulu_media.select_media_singular')).toBeInTheDocument();
    getLatestSingleSelectionStore().loading = true;
    expect(screen.getByText('…')).toBeInTheDocument();
});

test('Should open the edit overlay if editable and reload the item on confirm', async() => {
    const user = userEvent.setup();

    // $FlowFixMe
    mockSingleSelectionStoreOnce(function() {
        this.item = {id: 6, title: 'test media', mimeType: 'image/jpeg'};
        this.loadItem = jest.fn();
    });

    const itemClickSpy = jest.fn();

    render(
        <SingleMediaSelection
            editable={true}
            locale={observable.box('de')}
            onChange={jest.fn()}
            onItemClick={itemClickSpy}
            value={{displayOption: undefined, id: 6}}
        />
    );

    expect(screen.queryByText('save media 6 in de')).not.toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: /test media/}));
    expect(itemClickSpy).not.toHaveBeenCalled();

    await user.click(screen.getByRole('button', {name: 'save media 6 in de'}));
    expect(getLatestSingleSelectionStore().loadItem).toHaveBeenCalledWith(6);
    expect(screen.queryByText('save media 6 in de')).not.toBeInTheDocument();
});
