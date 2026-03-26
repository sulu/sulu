// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {Overlay as OverlayComponent} from 'sulu-admin-bundle/components';
import {SingleAutoComplete} from 'sulu-admin-bundle/containers';
import {MapContainer, Marker} from 'react-leaflet';
import LocationOverlay from '../LocationOverlay';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/components', () => {
    const actual = jest.requireActual('sulu-admin-bundle/components');

    return {
        ...actual,
        Overlay: jest.fn(function Overlay(props) {
            return <div data-testid="overlay">{props.children}</div>;
        }),
    };
});

jest.mock('sulu-admin-bundle/containers', () => ({
    SingleAutoComplete: jest.fn(() => null),
}));

jest.mock('react-leaflet', () => ({
    MapContainer: jest.fn(function MapContainer(props) {
        return <div data-testid="map-container">{props.children}</div>;
    }),
    Marker: jest.fn(function Marker(props) {
        return <div data-testid="marker">{props.children}</div>;
    }),
    TileLayer: jest.fn(() => null),
}));

function getLatestOverlayProps() {
    const calls = (OverlayComponent: any).mock.calls;

    return calls[calls.length - 1][0];
}

function getLatestSingleAutoCompleteProps() {
    const calls = (SingleAutoComplete: any).mock.calls;

    return calls[calls.length - 1][0];
}

function getLatestMapContainerProps() {
    const calls = (MapContainer: any).mock.calls;

    return calls[calls.length - 1][0];
}

function getLatestMarkerProps() {
    const calls = (Marker: any).mock.calls;

    return calls[calls.length - 1][0];
}

function renderLocationOverlay(props: Object = {}) {
    return render(
        <LocationOverlay
            locale="en"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            value={null}
            {...props}
        />
    );
}

function getLatInput() {
    return screen.getAllByRole('spinbutton')[0];
}

function getLongInput() {
    return screen.getAllByRole('spinbutton')[1];
}

function getZoomInput() {
    return screen.getAllByRole('spinbutton')[2];
}

function getTitleInput() {
    return screen.getAllByRole('textbox')[0];
}

function getStreetInput() {
    return screen.getAllByRole('textbox')[1];
}

function getNumberInput() {
    return screen.getAllByRole('textbox')[2];
}

function getCodeInput() {
    return screen.getAllByRole('textbox')[3];
}

function getTownInput() {
    return screen.getAllByRole('textbox')[4];
}

function getCountryInput() {
    return screen.getAllByRole('textbox')[5];
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Component should render without a given initial-value', () => {
    const {asFragment} = renderLocationOverlay();

    expect(asFragment()).toMatchSnapshot();
});

test('Component should render with a given initial-value', () => {
    const locationData = {
        code: 'code-123',
        country: undefined,
        lat: 22,
        long: 33,
        number: undefined,
        street: 'street-123',
        title: 'title-123',
        town: 'town-123',
        zoom: 5,
    };

    const {asFragment} = renderLocationOverlay({value: locationData});

    expect(asFragment()).toMatchSnapshot();
});

test('Should pass correct props the the Overlay component', () => {
    renderLocationOverlay();

    expect(getLatestOverlayProps()).toEqual(expect.objectContaining({
        confirmDisabled: false,
        confirmText: 'sulu_admin.confirm',
        open: true,
        size: 'small',
        title: 'sulu_location.select_location',
    }));
});

test('Should pass correct props the the SingleAutoComplete component', () => {
    renderLocationOverlay();

    expect(getLatestSingleAutoCompleteProps()).toEqual(expect.objectContaining({
        displayProperty: 'displayTitle',
        searchProperties: ['displayTitle'],
    }));
});

test('Should pass correct props the Map component and Marker component when no initial-value is given', () => {
    renderLocationOverlay();

    expect(getLatestMapContainerProps()).toEqual(expect.objectContaining({
        attributionControl: false,
        center: [0, 0],
        zoom: 1,
    }));

    expect(getLatestMarkerProps()).toEqual(expect.objectContaining({
        draggable: true,
        position: [0, 0],
    }));
});

test('Should pass correct props the Map component and Marker component when an initial-value is given', () => {
    const locationData = {
        code: 'code-123',
        country: undefined,
        lat: 22,
        long: 33,
        number: undefined,
        street: 'street-123',
        title: 'title-123',
        town: 'town-123',
        zoom: 5,
    };

    renderLocationOverlay({value: locationData});

    expect(getLatestMapContainerProps()).toEqual(expect.objectContaining({
        attributionControl: false,
        center: [22, 33],
        zoom: 5,
    }));

    expect(getLatestMarkerProps()).toEqual(expect.objectContaining({
        draggable: true,
        position: [22, 33],
    }));
});

test('Should pass correct props to the input fields', () => {
    const locationData = {
        code: 'code-123',
        country: undefined,
        lat: 22,
        long: 33,
        number: undefined,
        street: 'street-123',
        title: 'title-123',
        town: 'town-123',
        zoom: 5,
    };

    renderLocationOverlay({value: locationData});

    expect(getLatInput()).toHaveValue(22); // lat
    expect(getLongInput()).toHaveValue(33); // long
    expect(getZoomInput()).toHaveValue(5); // zoom
    expect(getTitleInput()).toHaveValue('title-123'); // title
    expect(getStreetInput()).toHaveValue('street-123'); // street
    expect(getNumberInput()).toHaveValue(''); // number
    expect(getCodeInput()).toHaveValue('code-123'); // code
    expect(getTownInput()).toHaveValue('town-123'); // town
    expect(getCountryInput()).toHaveValue(''); // country
});

test('Should pass correct props to the map, marker and input fields after auto-complete was changed', () => {
    renderLocationOverlay();

    const mockedMap = {setView: jest.fn(), on: jest.fn()};
    act(() => {
        getLatestMapContainerProps().whenCreated(mockedMap);
    });

    const autoCompleteResult = {
        latitude: 10,
        longitude: 20,
        displayTitle: 'new-display-title',
        street: 'new-street',
        number: 'new-number',
        code: 'new-code',
        town: 'new-town',
        country: 'new-country',
    };

    act(() => {
        getLatestSingleAutoCompleteProps().selectionStore.set(autoCompleteResult);
    });

    expect(getLatInput()).toHaveValue(autoCompleteResult.latitude); // lat
    expect(getLongInput()).toHaveValue(autoCompleteResult.longitude); // long
    expect(getZoomInput()).toHaveValue(1); // zoom
    expect(getTitleInput()).toHaveValue(autoCompleteResult.displayTitle); // title
    expect(getStreetInput()).toHaveValue(autoCompleteResult.street); // street
    expect(getNumberInput()).toHaveValue(autoCompleteResult.number); // number
    expect(getCodeInput()).toHaveValue(autoCompleteResult.code); // code
    expect(getTownInput()).toHaveValue(autoCompleteResult.town); // town
    expect(getCountryInput()).toHaveValue(autoCompleteResult.country); // country

    expect(mockedMap.setView).toBeCalledWith([autoCompleteResult.latitude, autoCompleteResult.longitude], 1);
    expect(getLatestMarkerProps().position).toEqual([autoCompleteResult.latitude, autoCompleteResult.longitude]);
});

test('Should call onConfirm callback when the Overlay is confirmed after auto-complete was changed', () => {
    const confirmSpy = jest.fn();
    renderLocationOverlay({onConfirm: confirmSpy});

    const autoCompleteResult = {
        latitude: 10,
        longitude: 20,
        displayTitle: 'new-display-title',
        street: 'new-street',
        number: 'new-number',
        code: 'new-code',
        town: 'new-town',
        country: 'new-country',
    };

    act(() => {
        getLatestSingleAutoCompleteProps().selectionStore.set(autoCompleteResult);
        getLatestOverlayProps().onConfirm();
    });

    expect(confirmSpy).toBeCalledWith(expect.objectContaining({
        lat: 10,
        long: 20,
        zoom: 1,
        title: 'new-display-title',
        street: 'new-street',
        number: 'new-number',
        code: 'new-code',
        town: 'new-town',
        country: 'new-country',
    }));
});

test('Should pass correct props to the map and input fields after map was zoomed', () => {
    renderLocationOverlay();

    const mockedMap = {setView: jest.fn(), on: jest.fn((event, handler) => {
        if (event === 'zoomanim') {
            handler({zoom: 10});
        }
    })};

    act(() => {
        getLatestMapContainerProps().whenCreated(mockedMap);
    });

    expect(getZoomInput()).toHaveValue(10); // zoom
    expect(getLatestMapContainerProps().zoom).toEqual(10);
});

test('Should call onConfirm callback when the Overlay is confirmed after map was zoomed', () => {
    const locationData = {
        lat: 1,
        long: 1,
        zoom: 1,
        title: 'old-title',
        street: 'old-street',
        number: 'old-number',
        code: 'old-code',
        town: 'old-town',
        country: 'old-country',
    };
    const confirmSpy = jest.fn();

    renderLocationOverlay({onConfirm: confirmSpy, value: locationData});

    const mockedMap = {setView: jest.fn(), on: jest.fn((event, handler) => {
        if (event === 'zoomanim') {
            handler({zoom: 10});
        }
    })};

    act(() => {
        getLatestMapContainerProps().whenCreated(mockedMap);
        getLatestOverlayProps().onConfirm();
    });

    expect(confirmSpy).toBeCalledWith(expect.objectContaining({
        lat: 1,
        long: 1,
        zoom: 10,
        title: 'old-title',
        street: 'old-street',
        number: 'old-number',
        code: 'old-code',
        town: 'old-town',
        country: 'old-country',
    }));
});

test('Should pass correct props to the map, marker and input fields when marker is dragged', () => {
    renderLocationOverlay();

    const mockedMap = {setView: jest.fn(), on: jest.fn()};
    act(() => {
        getLatestMapContainerProps().whenCreated(mockedMap);
    });

    act(() => {
        getLatestMarkerProps().eventHandlers.drag({latlng: {lng: 11, lat: 22}});
    });

    expect(getLatInput()).toHaveValue(22); // lat
    expect(getLongInput()).toHaveValue(11); // long
    expect(getLatestMarkerProps().position).toEqual([22, 11]);
    expect(mockedMap.setView).not.toBeCalled();

    act(() => {
        getLatestMarkerProps().eventHandlers.dragend();
    });

    expect(getLatInput()).toHaveValue(22); // lat
    expect(getLongInput()).toHaveValue(11); // long
    expect(getLatestMarkerProps().position).toEqual([22, 11]);
    expect(mockedMap.setView).toBeCalledWith([22, 11], 1);
});

test('Should call onConfirm callback when the Overlay is confirmed after marker was dragged', () => {
    const locationData = {
        lat: 1,
        long: 1,
        zoom: 1,
        title: 'old-title',
        street: 'old-street',
        number: 'old-number',
        code: 'old-code',
        town: 'old-town',
        country: 'old-country',
    };
    const confirmSpy = jest.fn();

    renderLocationOverlay({onConfirm: confirmSpy, value: locationData});

    act(() => {
        getLatestMarkerProps().eventHandlers.drag({latlng: {lng: 11, lat: 22}});
        getLatestMarkerProps().eventHandlers.dragend();
        getLatestOverlayProps().onConfirm();
    });

    expect(confirmSpy).toBeCalledWith(expect.objectContaining({
        lat: 22,
        long: 11,
        zoom: 1,
        title: 'old-title',
        street: 'old-street',
        number: 'old-number',
        code: 'old-code',
        town: 'old-town',
        country: 'old-country',
    }));
});

test('Should call onConfirm callback when the Overlay is confirmed after setting lat and ling to zero', async() => {
    const user = userEvent.setup();
    const locationData = {
        lat: 1,
        long: 1,
        zoom: 1,
        title: 'old-title',
        street: 'old-street',
        number: 'old-number',
        code: 'old-code',
        town: 'old-town',
        country: 'old-country',
    };
    const confirmSpy = jest.fn();

    renderLocationOverlay({onConfirm: confirmSpy, value: locationData});

    await user.clear(getLatInput());
    await user.type(getLatInput(), '0');
    await user.clear(getLongInput());
    await user.type(getLongInput(), '0');

    act(() => {
        getLatestOverlayProps().onConfirm();
    });

    expect(confirmSpy).toBeCalledWith(expect.objectContaining({
        lat: 0,
        long: 0,
        zoom: 1,
        title: 'old-title',
        street: 'old-street',
        number: 'old-number',
        code: 'old-code',
        town: 'old-town',
        country: 'old-country',
    }));
});

test('Should pass correct props to the map, marker and input fields after reset', () => {
    const locationData = {
        code: 'code-123',
        country: undefined,
        lat: 22,
        long: 33,
        number: undefined,
        street: 'street-123',
        title: 'title-123',
        town: 'town-123',
        zoom: 5,
    };

    renderLocationOverlay({value: locationData});

    const mockedMap = {setView: jest.fn(), on: jest.fn()};
    act(() => {
        getLatestMapContainerProps().whenCreated(mockedMap);
        getLatestOverlayProps().actions[0].onClick();
    });

    expect(getLatInput()).toHaveValue(null); // lat
    expect(getLongInput()).toHaveValue(null); // long
    expect(getZoomInput()).toHaveValue(1); // zoom
    expect(getTitleInput()).toHaveValue(''); // title
    expect(getStreetInput()).toHaveValue(''); // street
    expect(getNumberInput()).toHaveValue(''); // number
    expect(getCodeInput()).toHaveValue(''); // code
    expect(getTownInput()).toHaveValue(''); // town
    expect(getCountryInput()).toHaveValue(''); // country

    expect(mockedMap.setView).toBeCalledWith([0, 0], 1);
    expect(getLatestMarkerProps().position).toEqual([0, 0]);
});

test('Should call onConfirm callback when the Overlay is confirmed after reset', () => {
    const locationData = {
        lat: 1,
        long: 1,
        zoom: 1,
        title: 'old-title',
        street: 'old-street',
        number: 'old-number',
        code: 'old-code',
        town: 'old-town',
        country: 'old-country',
    };
    const confirmSpy = jest.fn();

    renderLocationOverlay({onConfirm: confirmSpy, value: locationData});

    act(() => {
        getLatestOverlayProps().actions[0].onClick();
        getLatestOverlayProps().onConfirm();
    });

    expect(confirmSpy).toBeCalledWith(null);
});
