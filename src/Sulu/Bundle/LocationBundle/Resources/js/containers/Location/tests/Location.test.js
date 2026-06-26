// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Location from '../Location';

let mockLocationOverlayProps: Object = {};
let mockMapContainerProps: Array<Object> = [];
let mockMarkerProps: Array<Object> = [];
let mockTooltipProps: Array<Object> = [];

const mockReact = require('react');

jest.mock('sulu-admin-bundle/utils/Translator');

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
    Tooltip: jest.fn((props) => {
        mockTooltipProps.push(props);

        return mockReact.createElement('div', {'data-testid': 'tooltip'}, props.children);
    }),
}));

jest.mock('../LocationOverlay', () => jest.fn((props) => {
    mockLocationOverlayProps = props;

    return mockReact.createElement(
        'div',
        {
            'data-open': String(props.open),
            'data-testid': 'location-overlay',
        }
    );
}));

beforeEach(() => {
    mockLocationOverlayProps = {};
    mockMapContainerProps = [];
    mockMarkerProps = [];
    mockTooltipProps = [];
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
        town: 'street-123',
        zoom: 5,
    };
}

function getLastMapContainerProps() {
    return mockMapContainerProps[mockMapContainerProps.length - 1];
}

function getLastMarkerProps() {
    return mockMarkerProps[mockMarkerProps.length - 1];
}

test('Component should render without a value', () => {
    const {asFragment} = render(
        <Location
            disabled={true}
            locale="en"
            onChange={jest.fn()}
            value={null}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Component should render in disabled state', () => {
    const {asFragment} = render(
        <Location
            disabled={true}
            locale="en"
            onChange={jest.fn()}
            value={null}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Component should render with a given value', () => {
    const {asFragment} = render(
        <Location
            disabled={true}
            locale="en"
            onChange={jest.fn()}
            value={getLocationData()}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Component should render a map, a marker and a tooltip with correct props and content', () => {
    render(
        <Location
            disabled={true}
            locale="en"
            onChange={jest.fn()}
            value={getLocationData()}
        />
    );

    expect(getLastMapContainerProps()).toEqual(expect.objectContaining({
        attributionControl: false,
        center: [22, 33],
        doubleClickZoom: false,
        dragging: false,
        keyboard: false,
        scrollWheelZoom: false,
        tap: false,
        zoom: 5,
        zoomControl: false,
    }));

    expect(getLastMarkerProps()).toEqual(expect.objectContaining({
        interactive: false,
        position: [22, 33],
    }));

    expect(mockTooltipProps[0]).toEqual(expect.objectContaining({
        permanent: true,
    }));

    expect(screen.getByTestId('tooltip')).toHaveTextContent('title-123');
    expect(screen.getByTestId('tooltip')).toHaveTextContent('code-123');
    expect(screen.getByTestId('tooltip')).toHaveTextContent('street-123');
});

test('Component should not render a tooltip if given value has no additional information', () => {
    const locationData = {
        code: undefined,
        country: undefined,
        lat: 22,
        long: 33,
        number: undefined,
        street: undefined,
        title: undefined,
        town: undefined,
        zoom: 5,
    };

    render(
        <Location
            disabled={true}
            locale="en"
            onChange={jest.fn()}
            value={locationData}
        />
    );

    expect(screen.queryByTestId('tooltip')).not.toBeInTheDocument();
});

test('Should pass correct props to the LocationOverlay', () => {
    const locationData = getLocationData();

    render(
        <Location
            disabled={true}
            locale="en"
            onChange={jest.fn()}
            value={locationData}
        />
    );

    expect(mockLocationOverlayProps).toEqual(expect.objectContaining({
        open: false,
        value: locationData,
    }));
});

test('Should open a LocationOverlay when the edit button is clicked', async() => {
    const user = userEvent.setup();
    render(
        <Location
            disabled={true}
            locale="en"
            onChange={jest.fn()}
            value={null}
        />
    );

    expect(screen.getByTestId('location-overlay')).toHaveAttribute('data-open', 'false');

    await user.click(screen.getByRole('button', {name: /su-map-pin/}));

    expect(screen.getByTestId('location-overlay')).toHaveAttribute('data-open', 'true');
});

test('Should close LocationOverlay when the onClose callback of the overlay is fired', async() => {
    const user = userEvent.setup();
    render(
        <Location
            disabled={true}
            locale="en"
            onChange={jest.fn()}
            value={null}
        />
    );

    await user.click(screen.getByRole('button', {name: /su-map-pin/}));
    expect(screen.getByTestId('location-overlay')).toHaveAttribute('data-open', 'true');

    act(() => {
        mockLocationOverlayProps.onClose();
    });

    expect(screen.getByTestId('location-overlay')).toHaveAttribute('data-open', 'false');
});

test('Should close overlay and call callback with correct value when the LocationOverlay is confirmed', async() => {
    const user = userEvent.setup();
    const newLocationData = getLocationData();
    const changeSpy = jest.fn();

    render(
        <Location
            disabled={true}
            locale="en"
            onChange={changeSpy}
            value={null}
        />
    );

    await user.click(screen.getByRole('button', {name: /su-map-pin/}));
    expect(screen.getByTestId('location-overlay')).toHaveAttribute('data-open', 'true');

    act(() => {
        mockLocationOverlayProps.onConfirm(newLocationData);
    });

    expect(screen.getByTestId('location-overlay')).toHaveAttribute('data-open', 'false');
    expect(changeSpy).toHaveBeenCalledWith(newLocationData);
});

test('Should update view of map when value prop is changed', () => {
    const locationData = getLocationData();
    const {rerender} = render(
        <Location
            disabled={true}
            locale="en"
            onChange={jest.fn()}
            value={locationData}
        />
    );

    const mockedMap = {setView: jest.fn()};
    getLastMapContainerProps().whenCreated(mockedMap);

    expect(mockedMap.setView).not.toHaveBeenCalled();

    rerender(
        <Location
            disabled={true}
            locale="en"
            onChange={jest.fn()}
            value={({lat: 44, long: 55, zoom: 2}: any)}
        />
    );

    expect(mockedMap.setView).toHaveBeenCalledWith([44, 55], 2);
});
