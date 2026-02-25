// @flow
import React from 'react';
import {observable} from 'mobx';
import {render} from '@testing-library/react';
import fieldTypeDefaultProps from 'sulu-admin-bundle/utils/TestHelper/fieldTypeDefaultProps';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import {MapContainer} from 'react-leaflet';
import {Location} from '../../../../containers/Form';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('react-leaflet', () => ({
    MapContainer: jest.fn(({children}) => <div>{children}</div>),
    Marker: jest.fn(() => null),
    TileLayer: jest.fn(() => null),
    Tooltip: jest.fn(() => null),
}));

jest.mock('../../../../containers/Location/LocationOverlay', () => jest.fn(() => null));

const locationOverlayComponent = ((jest.requireMock('../../../../containers/Location/LocationOverlay'): any): {
    mock: {calls: Array<[Object]>},
    ...
});

test('Pass props correctly to Location component', () => {
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

    render(
        <Location
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={({locale: observable.box('de')}: any)}
            value={locationData}
        />
    );

    expect(getLatestMockProps((MapContainer: any)).center).toEqual([22, 33]);
    expect(getLatestMockProps(locationOverlayComponent).value).toBe(locationData);
    expect(getLatestMockProps(locationOverlayComponent).locale).toBe('de');
});

test('Call onChange and onFinish when onChange callback of Location component is fired', () => {
    const newLocation = {
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

    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    render(
        <Location
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={({locale: observable.box('en')}: any)}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    getLatestMockProps(locationOverlayComponent).onConfirm(newLocation);

    expect(changeSpy).toBeCalledWith(newLocation);
    expect(finishSpy).toBeCalledWith();
});
