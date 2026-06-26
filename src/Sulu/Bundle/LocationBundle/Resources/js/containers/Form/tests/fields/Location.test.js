// @flow
import React from 'react';
import {observable} from 'mobx';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import fieldTypeDefaultProps from 'sulu-admin-bundle/utils/TestHelper/fieldTypeDefaultProps';
import {Location} from '../../../../containers/Form';

let mockLocationOverlayProps: Object = {};
let mockMapContainerProps: Array<Object> = [];

const mockReact = require('react');

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('react-leaflet', () => ({
    MapContainer: jest.fn((props) => {
        mockMapContainerProps.push(props);

        return mockReact.createElement('div', {'data-testid': 'map-container'}, props.children);
    }),
    Marker: jest.fn((props) => mockReact.createElement('div', {'data-testid': 'marker'}, props.children)),
    TileLayer: jest.fn(() => mockReact.createElement('div', {'data-testid': 'tile-layer'})),
    Tooltip: jest.fn((props) => mockReact.createElement('div', {'data-testid': 'tooltip'}, props.children)),
}));

jest.mock('../../../../containers/Location/LocationOverlay', () => jest.fn((props) => {
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
});

function createFormInspector(): any {
    return {
        locale: observable.box('en'),
    };
}

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

test('Pass props correctly to Location component', () => {
    const locationData = getLocationData();

    render(
        <Location
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={createFormInspector()}
            value={locationData}
        />
    );

    expect(screen.getAllByText(/sulu_location\.latitude: 22/).length).toBeGreaterThan(0);
    expect(mockMapContainerProps[0]).toEqual(expect.objectContaining({
        center: [22, 33],
        dragging: false,
        zoom: 5,
    }));
    expect(mockLocationOverlayProps.value).toBe(locationData);
});

test('Call onChange and onFinish when onChange callback of Location component is fired', async() => {
    const user = userEvent.setup();
    const newLocation = getLocationData();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    render(
        <Location
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={createFormInspector()}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    await user.click(screen.getByRole('button', {name: /su-map-pin/}));

    act(() => {
        mockLocationOverlayProps.onConfirm(newLocation);
    });

    expect(changeSpy).toHaveBeenCalledWith(newLocation);
    expect(finishSpy).toHaveBeenCalledWith();
});
