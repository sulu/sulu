// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import Location from '../Location';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('react-leaflet', () => {
    const React = require('react');

    const MapContainer = jest.fn(function MapContainerMock({children}) {
        return <div data-testid="map-container">{children}</div>;
    });
    const Marker = jest.fn(function MarkerMock({children}) {
        return <div data-testid="marker">{children}</div>;
    });
    const Tooltip = jest.fn(function TooltipMock({children}) {
        return <div data-testid="tooltip">{children}</div>;
    });
    const TileLayer = jest.fn(() => null);

    return {MapContainer, Marker, TileLayer, Tooltip};
});

jest.mock('../LocationOverlay', () => {
    const React = require('react');

    return jest.fn(function LocationOverlayMock({open}) {
        return <div data-open={open ? 'true' : 'false'} data-testid="location-overlay" />;
    });
});

const leaflet = (jest.requireMock('react-leaflet'): any);
const locationOverlay = (jest.requireMock('../LocationOverlay'): any);

function getLatestLocationOverlayProps(): any {
    return getLatestMockProps(locationOverlay);
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

    expect(getLatestMockProps(leaflet.MapContainer)).toEqual(expect.objectContaining({
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

    expect(getLatestMockProps(leaflet.Marker)).toEqual(expect.objectContaining({
        interactive: false,
        position: [22, 33],
    }));

    expect(getLatestMockProps(leaflet.Tooltip)).toEqual(expect.objectContaining({
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

    getLatestLocationOverlayProps().onClose();
    expect(getLatestLocationOverlayProps().open).toEqual(false);
});

test('Should close overlay and call callback with correct value when the LocationOverlay is confirmed', async() => {
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
    const user = userEvent.setup();

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

    getLatestLocationOverlayProps().onConfirm(newLocationData);
    expect(getLatestLocationOverlayProps().open).toEqual(false);
    expect(changeSpy).toHaveBeenCalledWith(newLocationData);
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
    getLatestMockProps(leaflet.MapContainer).whenCreated(mockedMap);

    expect(mockedMap.setView).not.toHaveBeenCalled();

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

    expect(mockedMap.setView).toHaveBeenCalledWith([44, 55], 2);
});
