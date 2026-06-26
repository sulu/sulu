// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import LocationOverlay from '../LocationOverlay';

let mockOverlayProps: Object = {};
let mockSingleAutoCompleteProps: Object = {};
let mockMapContainerProps: Array<Object> = [];
let mockMarkerProps: Array<Object> = [];

const mockReact = require('react');

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('sulu-admin-bundle/components', () => {
    const actual = jest.requireActual('sulu-admin-bundle/components');

    return {
        ...actual,
        Overlay: jest.fn((props) => {
            mockOverlayProps = props;

            return mockReact.createElement(
                'div',
                {
                    'data-open': String(props.open),
                    'data-testid': 'overlay',
                },
                props.children
            );
        }),
    };
});

jest.mock('sulu-admin-bundle/containers', () => ({
    SingleAutoComplete: jest.fn((props) => {
        mockSingleAutoCompleteProps = props;

        return mockReact.createElement('div', {'data-testid': 'single-autocomplete'});
    }),
}));

jest.mock('react-leaflet', () => ({
    MapContainer: jest.fn((props) => {
        mockMapContainerProps.push(props);

        return mockReact.createElement('div', {'data-testid': 'map-container'}, props.children);
    }),
    Marker: jest.fn((props) => {
        mockMarkerProps.push(props);

        return mockReact.createElement('div', {'data-testid': 'marker'}, props.children);
    }),
    TileLayer: jest.fn(() => mockReact.createElement('div', {'data-testid': 'tile-layer'})),
}));

beforeEach(() => {
    mockOverlayProps = {};
    mockSingleAutoCompleteProps = {};
    mockMapContainerProps = [];
    mockMarkerProps = [];
});

function getLocationData() {
    return {
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
}

function getConfirmLocationData() {
    return {
        code: 'old-code',
        country: 'old-country',
        lat: 1,
        long: 1,
        number: 'old-number',
        street: 'old-street',
        title: 'old-title',
        town: 'old-town',
        zoom: 1,
    };
}

function getAutoCompleteResult() {
    return {
        code: 'new-code',
        country: 'new-country',
        displayTitle: 'new-display-title',
        latitude: 10,
        longitude: 20,
        number: 'new-number',
        street: 'new-street',
        town: 'new-town',
    };
}

function renderLocationOverlay(props = {}) {
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

function getLastMapContainerProps() {
    return mockMapContainerProps[mockMapContainerProps.length - 1];
}

function getLastMarkerProps() {
    return mockMarkerProps[mockMarkerProps.length - 1];
}

function getNumberInputs() {
    return screen.getAllByRole('spinbutton');
}

function getTextInputs() {
    return screen.getAllByRole('textbox');
}

test('Component should render without a given initial-value', () => {
    const {asFragment} = renderLocationOverlay();

    expect(asFragment()).toMatchSnapshot();
});

test('Component should render with a given initial-value', () => {
    const {asFragment} = renderLocationOverlay({value: getLocationData()});

    expect(asFragment()).toMatchSnapshot();
});

test('Should pass correct props the the Overlay component', () => {
    renderLocationOverlay();

    expect(mockOverlayProps).toEqual(expect.objectContaining({
        confirmDisabled: false,
        confirmText: 'sulu_admin.confirm',
        open: true,
        size: 'small',
        title: 'sulu_location.select_location',
    }));
});

test('Should pass correct props the the SingleAutoComplete component', () => {
    renderLocationOverlay();

    expect(mockSingleAutoCompleteProps).toEqual(expect.objectContaining({
        displayProperty: 'displayTitle',
        searchProperties: ['displayTitle'],
    }));
});

test('Should pass correct props the Map component and Marker component when no initial-value is given', () => {
    renderLocationOverlay();

    expect(getLastMapContainerProps()).toEqual(expect.objectContaining({
        attributionControl: false,
        center: [0, 0],
        zoom: 1,
    }));

    expect(getLastMarkerProps()).toEqual(expect.objectContaining({
        draggable: true,
        position: [0, 0],
    }));
});

test('Should pass correct props the Map component and Marker component when an initial-value is given', () => {
    renderLocationOverlay({value: getLocationData()});

    expect(getLastMapContainerProps()).toEqual(expect.objectContaining({
        attributionControl: false,
        center: [22, 33],
        zoom: 5,
    }));

    expect(getLastMarkerProps()).toEqual(expect.objectContaining({
        draggable: true,
        position: [22, 33],
    }));
});

test('Should pass correct props to the input fields', () => {
    renderLocationOverlay({value: getLocationData()});

    expect(getNumberInputs()[0]).toHaveValue(22);
    expect(getNumberInputs()[1]).toHaveValue(33);
    expect(getNumberInputs()[2]).toHaveValue(5);
    expect(getTextInputs()[0]).toHaveValue('title-123');
    expect(getTextInputs()[1]).toHaveValue('street-123');
    expect(getTextInputs()[2]).toHaveValue('');
    expect(getTextInputs()[3]).toHaveValue('code-123');
    expect(getTextInputs()[4]).toHaveValue('town-123');
    expect(getTextInputs()[5]).toHaveValue('');
});

test('Should pass correct props to the map, marker and input fields after auto-complete was changed', () => {
    renderLocationOverlay();

    const mockedMap = {setView: jest.fn(), on: jest.fn()};
    act(() => {
        getLastMapContainerProps().whenCreated(mockedMap);
        mockSingleAutoCompleteProps.selectionStore.set(getAutoCompleteResult());
    });

    expect(getNumberInputs()[0]).toHaveValue(10);
    expect(getNumberInputs()[1]).toHaveValue(20);
    expect(getNumberInputs()[2]).toHaveValue(1);
    expect(getTextInputs()[0]).toHaveValue('new-display-title');
    expect(getTextInputs()[1]).toHaveValue('new-street');
    expect(getTextInputs()[2]).toHaveValue('new-number');
    expect(getTextInputs()[3]).toHaveValue('new-code');
    expect(getTextInputs()[4]).toHaveValue('new-town');
    expect(getTextInputs()[5]).toHaveValue('new-country');

    expect(mockedMap.setView).toHaveBeenCalledWith([10, 20], 1);
    expect(getLastMarkerProps().position).toEqual([10, 20]);
});

test('Should call onConfirm callback when the Overlay is confirmed after auto-complete was changed', () => {
    const confirmSpy = jest.fn();
    renderLocationOverlay({onConfirm: confirmSpy});

    act(() => {
        mockSingleAutoCompleteProps.selectionStore.set(getAutoCompleteResult());
        mockOverlayProps.onConfirm();
    });

    expect(confirmSpy).toHaveBeenCalledWith(expect.objectContaining({
        code: 'new-code',
        country: 'new-country',
        lat: 10,
        long: 20,
        number: 'new-number',
        street: 'new-street',
        title: 'new-display-title',
        town: 'new-town',
        zoom: 1,
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
        getLastMapContainerProps().whenCreated(mockedMap);
    });

    expect(getNumberInputs()[2]).toHaveValue(10);
    expect(getLastMapContainerProps().zoom).toEqual(10);
});

test('Should call onConfirm callback when the Overlay is confirmed after map was zoomed', () => {
    const confirmSpy = jest.fn();
    renderLocationOverlay({onConfirm: confirmSpy, value: getConfirmLocationData()});

    const mockedMap = {setView: jest.fn(), on: jest.fn((event, handler) => {
        if (event === 'zoomanim') {
            handler({zoom: 10});
        }
    })};

    act(() => {
        getLastMapContainerProps().whenCreated(mockedMap);
        mockOverlayProps.onConfirm();
    });

    expect(confirmSpy).toHaveBeenCalledWith(expect.objectContaining({
        code: 'old-code',
        country: 'old-country',
        lat: 1,
        long: 1,
        number: 'old-number',
        street: 'old-street',
        title: 'old-title',
        town: 'old-town',
        zoom: 10,
    }));
});

test('Should pass correct props to the map, marker and input fields when marker is dragged', () => {
    renderLocationOverlay();

    const mockedMap = {setView: jest.fn(), on: jest.fn()};
    act(() => {
        getLastMapContainerProps().whenCreated(mockedMap);
        getLastMarkerProps().eventHandlers.drag({latlng: {lng: 11, lat: 22}});
    });

    expect(getNumberInputs()[0]).toHaveValue(22);
    expect(getNumberInputs()[1]).toHaveValue(11);
    expect(getLastMarkerProps().position).toEqual([22, 11]);
    expect(mockedMap.setView).not.toHaveBeenCalled();

    act(() => {
        getLastMarkerProps().eventHandlers.dragend();
    });

    expect(getNumberInputs()[0]).toHaveValue(22);
    expect(getNumberInputs()[1]).toHaveValue(11);
    expect(getLastMarkerProps().position).toEqual([22, 11]);
    expect(mockedMap.setView).toHaveBeenCalledWith([22, 11], 1);
});

test('Should call onConfirm callback when the Overlay is confirmed after marker was dragged', () => {
    const confirmSpy = jest.fn();
    renderLocationOverlay({onConfirm: confirmSpy, value: getConfirmLocationData()});

    act(() => {
        getLastMarkerProps().eventHandlers.drag({latlng: {lng: 11, lat: 22}});
        getLastMarkerProps().eventHandlers.dragend();
        mockOverlayProps.onConfirm();
    });

    expect(confirmSpy).toHaveBeenCalledWith(expect.objectContaining({
        code: 'old-code',
        country: 'old-country',
        lat: 22,
        long: 11,
        number: 'old-number',
        street: 'old-street',
        title: 'old-title',
        town: 'old-town',
        zoom: 1,
    }));
});

test('Should call onConfirm callback when the Overlay is confirmed after setting lat and ling to zero', async() => {
    const user = userEvent.setup();
    const confirmSpy = jest.fn();
    renderLocationOverlay({onConfirm: confirmSpy, value: getConfirmLocationData()});

    await user.clear(getNumberInputs()[0]);
    await user.type(getNumberInputs()[0], '0');
    await user.clear(getNumberInputs()[1]);
    await user.type(getNumberInputs()[1], '0');

    act(() => {
        mockOverlayProps.onConfirm();
    });

    expect(confirmSpy).toHaveBeenCalledWith(expect.objectContaining({
        code: 'old-code',
        country: 'old-country',
        lat: 0,
        long: 0,
        number: 'old-number',
        street: 'old-street',
        title: 'old-title',
        town: 'old-town',
        zoom: 1,
    }));
});

test('Should pass correct props to the map, marker and input fields after reset', () => {
    renderLocationOverlay({value: getLocationData()});

    const mockedMap = {setView: jest.fn(), on: jest.fn()};
    act(() => {
        getLastMapContainerProps().whenCreated(mockedMap);
        mockOverlayProps.actions[0].onClick();
    });

    expect(getNumberInputs()[0]).toHaveValue(null);
    expect(getNumberInputs()[1]).toHaveValue(null);
    expect(getNumberInputs()[2]).toHaveValue(1);
    expect(getTextInputs()[0]).toHaveValue('');
    expect(getTextInputs()[1]).toHaveValue('');
    expect(getTextInputs()[2]).toHaveValue('');
    expect(getTextInputs()[3]).toHaveValue('');
    expect(getTextInputs()[4]).toHaveValue('');
    expect(getTextInputs()[5]).toHaveValue('');

    expect(mockedMap.setView).toHaveBeenCalledWith([0, 0], 1);
    expect(getLastMarkerProps().position).toEqual([0, 0]);
});

test('Should call onConfirm callback when the Overlay is confirmed after reset', () => {
    const confirmSpy = jest.fn();
    renderLocationOverlay({onConfirm: confirmSpy, value: getConfirmLocationData()});

    act(() => {
        mockOverlayProps.actions[0].onClick();
        mockOverlayProps.onConfirm();
    });

    expect(confirmSpy).toHaveBeenCalledWith(null);
});
