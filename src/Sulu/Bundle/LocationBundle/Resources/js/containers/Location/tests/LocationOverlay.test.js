// @flow
import React from 'react';
import {act, render} from '@testing-library/react';
import {Input, Number, Overlay} from 'sulu-admin-bundle/components';
import {SingleAutoComplete} from 'sulu-admin-bundle/containers';
import {MapContainer, Marker} from 'react-leaflet';
import {getLatestMockProps, getMockCallArg} from 'sulu-admin-bundle/utils/TestHelper';
import LocationOverlay from '../LocationOverlay';

jest.mock('sulu-admin-bundle/components', () => {
    const React = require('react');
    const actual = jest.requireActual('sulu-admin-bundle/components');

    return {
        ...actual,
        Input: jest.fn((props) => {
            const value = props.value == null ? '' : props.value;
            return <div data-testid="input" data-value={value} />;
        }),
        Number: jest.fn((props) => {
            const value = props.value == null ? '' : String(props.value);
            return <div data-testid="number" data-value={value} />;
        }),
        Overlay: jest.fn((props) => (
            <div data-testid="overlay">
                {props.children}
            </div>
        )),
    };
});

jest.mock('sulu-admin-bundle/containers', () => ({
    SingleAutoComplete: jest.fn(() => <div data-testid="single-autocomplete" />),
}));

jest.mock('react-leaflet', () => ({
    MapContainer: jest.fn((props) => (
        <div data-testid="map-container">
            {props.children}
        </div>
    )),
    Marker: jest.fn(() => <div data-testid="marker" />),
    TileLayer: jest.fn(() => null),
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

const InputMock = (Input: any);
const NumberMock = (Number: any);
const OverlayMock = (Overlay: any);
const SingleAutoCompleteMock = (SingleAutoComplete: any);
const MapContainerMock = (MapContainer: any);
const MarkerMock = (Marker: any);

const createLocationData = () => ({
    code: 'code-123',
    country: undefined,
    lat: 22,
    long: 33,
    number: undefined,
    street: 'street-123',
    title: 'title-123',
    town: 'town-123',
    zoom: 5,
});

const createProps = (overrides = {}): any => ({
    locale: 'en',
    onClose: jest.fn(),
    onConfirm: jest.fn(),
    open: true,
    value: null,
    ...overrides,
});

const renderLocationOverlay = (overrides = {}) => {
    return render(<LocationOverlay {...createProps(overrides)} />);
};

const getLatestOverlayProps = () => getLatestMockProps(OverlayMock);

const getLatestSingleAutoCompleteProps = () => getLatestMockProps(SingleAutoCompleteMock);

const getLatestMapContainerProps = () => getLatestMockProps(MapContainerMock);

const getLatestMarkerProps = () => getLatestMockProps(MarkerMock);

const getLatestNumberProps = (index) => getMockCallArg(NumberMock, -3 + index);

const getLatestInputProps = (index) => getMockCallArg(InputMock, -6 + index);

beforeEach(() => {
    jest.clearAllMocks();
});

test('Component should render without a given initial-value', () => {
    const {asFragment} = renderLocationOverlay();

    expect(asFragment()).toMatchSnapshot();
});

test('Component should render with a given initial-value', () => {
    const {asFragment} = renderLocationOverlay({value: createLocationData()});

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
    renderLocationOverlay({value: createLocationData()});

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
    renderLocationOverlay({value: createLocationData()});

    expect(getLatestNumberProps(0).value).toEqual(22);
    expect(getLatestNumberProps(1).value).toEqual(33);
    expect(getLatestNumberProps(2).value).toEqual(5);
    expect(getLatestInputProps(0).value).toEqual('title-123');
    expect(getLatestInputProps(1).value).toEqual('street-123');
    expect(getLatestInputProps(2).value).toBeUndefined();
    expect(getLatestInputProps(3).value).toEqual('code-123');
    expect(getLatestInputProps(4).value).toEqual('town-123');
    expect(getLatestInputProps(5).value).toBeUndefined();
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

    expect(getLatestNumberProps(0).value).toEqual(autoCompleteResult.latitude);
    expect(getLatestNumberProps(1).value).toEqual(autoCompleteResult.longitude);
    expect(getLatestNumberProps(2).value).toEqual(1);
    expect(getLatestInputProps(0).value).toEqual(autoCompleteResult.displayTitle);
    expect(getLatestInputProps(1).value).toEqual(autoCompleteResult.street);
    expect(getLatestInputProps(2).value).toEqual(autoCompleteResult.number);
    expect(getLatestInputProps(3).value).toEqual(autoCompleteResult.code);
    expect(getLatestInputProps(4).value).toEqual(autoCompleteResult.town);
    expect(getLatestInputProps(5).value).toEqual(autoCompleteResult.country);

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
    });

    act(() => {
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

    const mockedMap = {
        setView: jest.fn(),
        on: jest.fn((event, handler) => {
            if (event === 'zoomanim') {
                handler({zoom: 10});
            }
        }),
    };

    act(() => {
        getLatestMapContainerProps().whenCreated(mockedMap);
    });

    expect(getLatestNumberProps(2).value).toEqual(10);
    expect(getLatestMapContainerProps().zoom).toEqual(10);
});

test('Should call onConfirm callback when the Overlay is confirmed after map was zoomed', () => {
    const confirmSpy = jest.fn();

    renderLocationOverlay({
        onConfirm: confirmSpy,
        value: {
            lat: 1,
            long: 1,
            zoom: 1,
            title: 'old-title',
            street: 'old-street',
            number: 'old-number',
            code: 'old-code',
            town: 'old-town',
            country: 'old-country',
        },
    });

    const mockedMap = {
        setView: jest.fn(),
        on: jest.fn((event, handler) => {
            if (event === 'zoomanim') {
                handler({zoom: 10});
            }
        }),
    };

    act(() => {
        getLatestMapContainerProps().whenCreated(mockedMap);
    });

    act(() => {
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

    expect(getLatestNumberProps(0).value).toEqual(22);
    expect(getLatestNumberProps(1).value).toEqual(11);
    expect(getLatestMarkerProps().position).toEqual([22, 11]);
    expect(mockedMap.setView).not.toBeCalled();

    act(() => {
        getLatestMarkerProps().eventHandlers.dragend();
    });

    expect(getLatestNumberProps(0).value).toEqual(22);
    expect(getLatestNumberProps(1).value).toEqual(11);
    expect(getLatestMarkerProps().position).toEqual([22, 11]);
    expect(mockedMap.setView).toBeCalledWith([22, 11], 1);
});

test('Should call onConfirm callback when the Overlay is confirmed after marker was dragged', () => {
    const confirmSpy = jest.fn();

    renderLocationOverlay({
        onConfirm: confirmSpy,
        value: {
            lat: 1,
            long: 1,
            zoom: 1,
            title: 'old-title',
            street: 'old-street',
            number: 'old-number',
            code: 'old-code',
            town: 'old-town',
            country: 'old-country',
        },
    });

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

test('Should call onConfirm callback when the Overlay is confirmed after setting lat and ling to zero', () => {
    const confirmSpy = jest.fn();

    renderLocationOverlay({
        onConfirm: confirmSpy,
        value: {
            lat: 1,
            long: 1,
            zoom: 1,
            title: 'old-title',
            street: 'old-street',
            number: 'old-number',
            code: 'old-code',
            town: 'old-town',
            country: 'old-country',
        },
    });

    act(() => {
        getLatestNumberProps(0).onChange(0);
        getLatestNumberProps(1).onChange(0);
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
    renderLocationOverlay({value: createLocationData()});

    const mockedMap = {setView: jest.fn(), on: jest.fn()};

    act(() => {
        getLatestMapContainerProps().whenCreated(mockedMap);
    });

    act(() => {
        getLatestOverlayProps().actions[0].onClick();
    });

    expect(getLatestNumberProps(0).value).toBeNull();
    expect(getLatestNumberProps(1).value).toBeNull();
    expect(getLatestNumberProps(2).value).toEqual(1);
    expect(getLatestInputProps(0).value).toBeNull();
    expect(getLatestInputProps(1).value).toBeNull();
    expect(getLatestInputProps(2).value).toBeNull();
    expect(getLatestInputProps(3).value).toBeNull();
    expect(getLatestInputProps(4).value).toBeNull();
    expect(getLatestInputProps(5).value).toBeNull();

    expect(mockedMap.setView).toBeCalledWith([0, 0], 1);
    expect(getLatestMarkerProps().position).toEqual([0, 0]);
});

test('Should call onConfirm callback when the Overlay is confirmed after reset', () => {
    const confirmSpy = jest.fn();

    renderLocationOverlay({
        onConfirm: confirmSpy,
        value: {
            lat: 1,
            long: 1,
            zoom: 1,
            title: 'old-title',
            street: 'old-street',
            number: 'old-number',
            code: 'old-code',
            town: 'old-town',
            country: 'old-country',
        },
    });

    act(() => {
        getLatestOverlayProps().actions[0].onClick();
        getLatestOverlayProps().onConfirm();
    });

    expect(confirmSpy).toBeCalledWith(null);
});

test('Should pass correct props to the map, marker and input fields after input fields are changed', () => {
    renderLocationOverlay();

    const mockedMap = {setView: jest.fn(), on: jest.fn()};

    act(() => {
        getLatestMapContainerProps().whenCreated(mockedMap);
    });

    act(() => {
        getLatestNumberProps(0).onChange(10);
        getLatestNumberProps(1).onChange(20);
        getLatestNumberProps(2).onChange(12);
        getLatestInputProps(0).onChange('new-title');
        getLatestInputProps(1).onChange('new-street');
        getLatestInputProps(2).onChange('new-number');
        getLatestInputProps(3).onChange('new-code');
        getLatestInputProps(4).onChange('new-town');
        getLatestInputProps(5).onChange('new-country');
    });

    expect(getLatestNumberProps(0).value).toEqual(10);
    expect(getLatestNumberProps(1).value).toEqual(20);
    expect(getLatestNumberProps(2).value).toEqual(12);
    expect(getLatestInputProps(0).value).toEqual('new-title');
    expect(getLatestInputProps(1).value).toEqual('new-street');
    expect(getLatestInputProps(2).value).toEqual('new-number');
    expect(getLatestInputProps(3).value).toEqual('new-code');
    expect(getLatestInputProps(4).value).toEqual('new-town');
    expect(getLatestInputProps(5).value).toEqual('new-country');

    expect(mockedMap.setView).toBeCalledWith([10, 20], 12);
    expect(getLatestMarkerProps().position).toEqual([10, 20]);
});

test('Should call onConfirm callback when the Overlay is confirmed after input fields are changed', () => {
    const confirmSpy = jest.fn();

    renderLocationOverlay({onConfirm: confirmSpy});

    act(() => {
        getLatestNumberProps(0).onChange(10);
        getLatestNumberProps(1).onChange(20);
        getLatestNumberProps(2).onChange(12);
        getLatestInputProps(0).onChange('new-title');
        getLatestInputProps(1).onChange('new-street');
        getLatestInputProps(2).onChange('new-number');
        getLatestInputProps(3).onChange('new-code');
        getLatestInputProps(4).onChange('new-town');
        getLatestInputProps(5).onChange('new-country');
        getLatestOverlayProps().onConfirm();
    });

    expect(confirmSpy).toBeCalledWith(expect.objectContaining({
        lat: 10,
        long: 20,
        zoom: 12,
        title: 'new-title',
        street: 'new-street',
        number: 'new-number',
        code: 'new-code',
        town: 'new-town',
        country: 'new-country',
    }));
});

test('Should call given onClose callback when onClose callback of Overlay is fired', () => {
    const closeSpy = jest.fn();

    renderLocationOverlay({onClose: closeSpy});

    act(() => {
        getLatestOverlayProps().onClose();
    });

    expect(closeSpy).toBeCalledWith();
});

test('Should enable confirm button if longitude and latitude are both not set or both set', () => {
    renderLocationOverlay();

    act(() => {
        getLatestNumberProps(0).onChange(null);
        getLatestNumberProps(1).onChange(null);
    });
    expect(getLatestOverlayProps().confirmDisabled).toEqual(false);

    act(() => {
        getLatestNumberProps(0).onChange(11);
        getLatestNumberProps(1).onChange(null);
    });
    expect(getLatestOverlayProps().confirmDisabled).toEqual(true);

    act(() => {
        getLatestNumberProps(0).onChange(null);
        getLatestNumberProps(1).onChange(11);
    });
    expect(getLatestOverlayProps().confirmDisabled).toEqual(true);

    act(() => {
        getLatestNumberProps(0).onChange(11);
        getLatestNumberProps(1).onChange(11);
    });
    expect(getLatestOverlayProps().confirmDisabled).toEqual(false);

    act(() => {
        getLatestNumberProps(0).onChange(0);
        getLatestNumberProps(1).onChange(11);
    });
    expect(getLatestOverlayProps().confirmDisabled).toEqual(false);

    act(() => {
        getLatestNumberProps(0).onChange(11);
        getLatestNumberProps(1).onChange(0);
    });
    expect(getLatestOverlayProps().confirmDisabled).toEqual(false);

    act(() => {
        getLatestNumberProps(0).onChange(0);
        getLatestNumberProps(1).onChange(0);
    });
    expect(getLatestOverlayProps().confirmDisabled).toEqual(false);
});
