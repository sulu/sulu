// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {MapContainer, Marker, Tooltip} from 'react-leaflet';
import LocationOverlayComponent from '../LocationOverlay';
import Location from '../Location';

jest.mock('../LocationOverlay', () => jest.fn(() => null));

jest.mock('react-leaflet', () => ({
    MapContainer: jest.fn(function MapContainer(props) {
        return <div data-testid="map-container">{props.children}</div>;
    }),
    Marker: jest.fn(function Marker(props) {
        return <div data-testid="marker">{props.children}</div>;
    }),
    TileLayer: jest.fn(() => null),
    Tooltip: jest.fn(function Tooltip(props) {
        return <div data-testid="tooltip">{props.children}</div>;
    }),
}));

function getLatestMapContainerProps() {
    const calls = (MapContainer: any).mock.calls;

    return calls[calls.length - 1][0];
}

function getLatestMarkerProps() {
    const calls = (Marker: any).mock.calls;

    return calls[calls.length - 1][0];
}

function getLatestTooltipProps() {
    const calls = (Tooltip: any).mock.calls;

    return calls[calls.length - 1][0];
}

function getLatestLocationOverlayProps() {
    const calls = (LocationOverlayComponent: any).mock.calls;

    return calls[calls.length - 1][0];
}

beforeEach(() => {
    jest.clearAllMocks();
});

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

    const {asFragment} = render(
        <Location
            disabled={true}
            locale="en"
            onChange={jest.fn()}
            value={locationData}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Component should render a map, a marker and a tooltip with correct props and content', () => {
    const locationData = {
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

    render(
        <Location
            disabled={true}
            locale="en"
            onChange={jest.fn()}
            value={locationData}
        />
    );

    expect(getLatestMapContainerProps()).toEqual(expect.objectContaining({
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

    expect(getLatestMarkerProps()).toEqual(expect.objectContaining({
        interactive: false,
        position: [22, 33],
    }));

    expect(getLatestTooltipProps()).toEqual(expect.objectContaining({
        permanent: true,
    }));

    expect(screen.getByTestId('tooltip')).toHaveTextContent('title-123');
    expect(screen.getByTestId('tooltip')).toHaveTextContent('code-123');
    expect(screen.getByTestId('tooltip')).toHaveTextContent('street-123');
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
    const locationData = {
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

    render(
        <Location
            disabled={true}
            locale="en"
            onChange={jest.fn()}
            value={locationData}
        />
    );

    expect(getLatestLocationOverlayProps()).toEqual(expect.objectContaining({
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

    expect(getLatestLocationOverlayProps().open).toEqual(false);
    await user.click(screen.getByRole('button'));
    expect(getLatestLocationOverlayProps().open).toEqual(true);
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

    await user.click(screen.getByRole('button'));
    expect(getLatestLocationOverlayProps().open).toEqual(true);

    act(() => {
        getLatestLocationOverlayProps().onClose();
    });
    expect(getLatestLocationOverlayProps().open).toEqual(false);
});

test('Should close overlay and call callback with correct value when the LocationOverlay is confirmed', async() => {
    const user = userEvent.setup();
    const newLocationData = {
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
    const changeSpy = jest.fn();

    render(
        <Location
            disabled={true}
            locale="en"
            onChange={changeSpy}
            value={null}
        />
    );

    await user.click(screen.getByRole('button'));
    expect(getLatestLocationOverlayProps().open).toEqual(true);

    act(() => {
        getLatestLocationOverlayProps().onConfirm(newLocationData);
    });
    expect(getLatestLocationOverlayProps().open).toEqual(false);
    expect(changeSpy).toBeCalledWith(newLocationData);
});

test('Should update view of map when value prop is changed', () => {
    const locationData = {
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

    const {rerender} = render(
        <Location
            disabled={true}
            locale="en"
            onChange={jest.fn()}
            value={locationData}
        />
    );

    const mockedMap = {setView: jest.fn()};
    act(() => {
        getLatestMapContainerProps().whenCreated(mockedMap);
    });

    expect(mockedMap.setView).not.toBeCalled();

    rerender(
        <Location
            disabled={true}
            locale="en"
            onChange={jest.fn()}
            value={{
                code: undefined,
                country: undefined,
                lat: 44,
                long: 55,
                number: undefined,
                street: undefined,
                title: undefined,
                town: undefined,
                zoom: 2,
            }}
        />
    );

    expect(mockedMap.setView).toBeCalledWith([44, 55], 2);
});
