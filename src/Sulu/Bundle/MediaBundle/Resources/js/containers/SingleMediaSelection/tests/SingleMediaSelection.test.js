// @flow
import React from 'react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import SingleItemSelection from 'sulu-admin-bundle/components/SingleItemSelection';
import SingleSelectionStore from 'sulu-admin-bundle/stores/SingleSelectionStore';
import {findElementByType, renderWithRef} from 'sulu-admin-bundle/utils/TestHelper';
import SingleMediaSelection from '../SingleMediaSelection';
import SingleMediaSelectionOverlay from '../../SingleMediaSelectionOverlay';

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('../../SingleMediaSelectionOverlay', () => jest.fn(function() {
    return <div>single media selection overlay</div>;
}));

jest.mock('sulu-admin-bundle/stores/SingleSelectionStore', () => jest.fn());

const SingleMediaSelectionOverlayMock = (SingleMediaSelectionOverlay: any);

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
    SingleSelectionStore.mockImplementationOnce(function() {
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
    SingleSelectionStore.mockImplementationOnce(function() {
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
    const {instance: singleMediaSelection} = renderWithRef(
        <SingleMediaSelection
            className="test"
            locale={observable.box('en')}
            onChange={jest.fn()}
            value={undefined}
        />
    );

    expect(findElementByType(singleMediaSelection.render(), SingleItemSelection).props.className).toEqual('test');
});

test('Component should pass types to SingleMediaSelectionOverlay', () => {
    const {instance: singleMediaSelection} = renderWithRef(
        <SingleMediaSelection
            locale={observable.box('en')}
            onChange={jest.fn()}
            types={['image', 'video']}
            value={undefined}
        />
    );

    expect(findElementByType(singleMediaSelection.render(), SingleMediaSelectionOverlay).props.types)
        .toEqual(['image', 'video']);
});

test('Click on media-button should open an overlay', async() => {
    const user = userEvent.setup();
    render(<SingleMediaSelection locale={observable.box('en')} onChange={jest.fn()} value={undefined} />);

    expect(SingleMediaSelectionOverlayMock.mock.calls[0][0].open).toEqual(false);
    await user.click(screen.getByRole('button', {name: 'su-image'}));
    expect(SingleMediaSelectionOverlayMock.mock.calls[SingleMediaSelectionOverlayMock.mock.calls.length - 1][0].open)
        .toEqual(true);
});

test('Click on remove-button should clear the selection store', async() => {
    const user = userEvent.setup();
    // $FlowFixMe
    SingleSelectionStore.mockImplementationOnce(function() {
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

    const {instance: singleMediaSelection} = renderWithRef(
        <SingleMediaSelection
            locale={observable.box('en')}
            onChange={jest.fn()}
            value={{displayOption: undefined, id: 33}}
        />
    );

    await user.click(screen.getByRole('button', {name: 'su-trash-alt'}));
    expect(singleMediaSelection.singleMediaSelectionStore.clear).toHaveBeenCalled();
});

test('Media that is selected in the overlay should be set to the selection store on confirm', () => {
    // $FlowFixMe
    SingleSelectionStore.mockImplementationOnce(function() {
        this.set = jest.fn();
    });

    const {instance: singleMediaSelection} = renderWithRef(
        <SingleMediaSelection locale={observable.box('en')} onChange={jest.fn()} value={undefined} />
    );

    singleMediaSelection.handleOverlayConfirm({
        id: 22,
        title: 'test media',
        mimeType: 'image/jpeg',
        thumbnails: {
            'sulu-25x25': '/images/25x25/awesome.png',
        },
    });

    expect(singleMediaSelection.singleMediaSelectionStore.set).toHaveBeenCalledWith(expect.objectContaining({
        id: 22,
        title: 'test media',
        mimeType: 'image/jpeg',
        thumbnails: {
            'sulu-25x25': '/images/25x25/awesome.png',
        },
    }));
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
    SingleSelectionStore.mockImplementationOnce(function() {
        this.loadItem = jest.fn();
        mockExtendObservable(this, {
            item: undefined,
        });
    });

    const changeSpy = jest.fn();

    const {instance: singleMediaSelectionInstance} = renderWithRef(
        <SingleMediaSelection locale={observable.box('en')} onChange={changeSpy} value={undefined} />
    );

    expect(changeSpy).not.toHaveBeenCalled();
    singleMediaSelectionInstance.singleMediaSelectionStore.item = {
        id: 77,
        title: 'test media',
        mimeType: 'image/jpeg',
        thumbnails: {},
    };
    expect(changeSpy).toHaveBeenCalledWith({id: 77}, singleMediaSelectionInstance.singleMediaSelectionStore.item);
});

test('Should not call onChange callback if an unrelated observable that is accessed in the callback changes', () => {
    // $FlowFixMe
    SingleSelectionStore.mockImplementationOnce(function() {
        this.loadItem = jest.fn();
        mockExtendObservable(this, {
            item: undefined,
        });
    });

    const unrelatedObservable = observable.box(22);
    const changeSpy = jest.fn(() => {
        jest.fn()(unrelatedObservable.get());
    });

    const {instance: singleMediaSelectionInstance} = renderWithRef(
        <SingleMediaSelection locale={observable.box('en')} onChange={changeSpy} value={undefined} />
    );

    // change callback should be called when item of the store mock changes
    singleMediaSelectionInstance.singleMediaSelectionStore.item = {id: 77, mimeType: 'image/jpeg', thumbnails: {}};
    expect(changeSpy).toHaveBeenCalledWith({id: 77}, singleMediaSelectionInstance.singleMediaSelectionStore.item);
    expect(changeSpy).toHaveBeenCalledTimes(1);

    // change callback should not be called when the unrelated observable changes
    unrelatedObservable.set(55);
    expect(changeSpy).toHaveBeenCalledTimes(1);
});

test('Should not call the onChange callback if the component props change', () => {
    // $FlowFixMe
    SingleSelectionStore.mockImplementationOnce(function() {
        this.loadItem = jest.fn();
    });

    const changeSpy = jest.fn();

    const {rerender} = renderWithRef(
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
    SingleSelectionStore.mockImplementationOnce(function() {
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

    await user.click(screen.getByText('sulu_media.select_media_singular'));
    expect(itemClickSpy).not.toHaveBeenCalled();
});

test('Should call the onItemClick callback if the item is clicked', () => {
    // $FlowFixMe
    SingleSelectionStore.mockImplementationOnce(function() {
        this.item = {id: 6, mimeType: 'image/jpeg'};
    });

    const itemClickSpy = jest.fn();

    const {instance: singleMediaSelection} = renderWithRef(
        <SingleMediaSelection
            locale={observable.box('en')}
            onChange={jest.fn()}
            onItemClick={itemClickSpy}
            value={{displayOption: undefined, id: 5}}
        />
    );

    singleMediaSelection.handleItemClick(6, {id: 6, mimeType: 'image/jpeg'});
    expect(itemClickSpy).toHaveBeenCalledWith(6, {id: 6, mimeType: 'image/jpeg'});
});

test('Should not call the loadItem callback if the component props id change to same value', () => {
    // $FlowFixMe
    SingleSelectionStore.mockImplementationOnce(function() {
        this.loadItem = jest.fn();
    });

    const changeSpy = jest.fn();

    const {instance: singleMediaSelection, rerender} = renderWithRef(
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
    expect(singleMediaSelection.singleMediaSelectionStore.loadItem).not.toHaveBeenCalled();
});

test('Correct props should be passed to SingleItemSelection component', () => {
    const {instance: singleMediaSelection} = renderWithRef(
        <SingleMediaSelection
            disabled={true}
            locale={observable.box('en')}
            onChange={jest.fn()}
            valid={false}
            value={undefined}
        />
    );
    const singleItemSelectionProps = findElementByType(singleMediaSelection.render(), SingleItemSelection).props;

    expect(singleItemSelectionProps.disabled).toEqual(true);
    expect(singleItemSelectionProps.valid).toEqual(false);
});

test('Set loading prop of SingleItemSelection component if SingleSelectionStore is loading', () => {
    // $FlowFixMe
    SingleSelectionStore.mockImplementationOnce(function() {
        mockExtendObservable(this, {
            loading: false,
        });
    });

    const {instance: singleMediaSelection} = renderWithRef(
        <SingleMediaSelection disabled={true} locale={observable.box('en')} onChange={jest.fn()} value={undefined} />
    );

    expect(findElementByType(singleMediaSelection.render(), SingleItemSelection).props.loading).toEqual(false);
    singleMediaSelection.singleMediaSelectionStore.loading = true;
    expect(findElementByType(singleMediaSelection.render(), SingleItemSelection).props.loading).toEqual(true);
});
