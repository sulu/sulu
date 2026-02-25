// @flow
import React from 'react';
import {render} from '@testing-library/react';
import fieldTypeDefaultProps from 'sulu-admin-bundle/utils/TestHelper/fieldTypeDefaultProps';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import ResourceFormStore from 'sulu-admin-bundle/containers/Form/stores/ResourceFormStore';
import {FormInspector} from 'sulu-admin-bundle/containers/Form';
import {Location} from '../../../../containers/Form';
import LocationComponent from '../../../../containers/Location';

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn());
jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn());
jest.mock('sulu-admin-bundle/containers/Form/FormInspector', () => jest.fn());
jest.mock('../../../../containers/Location', () => jest.fn(() => null));

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

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    render(
        <Location
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value={locationData}
        />
    );

    const locationProps: any = getLatestMockProps((LocationComponent: any));
    expect(locationProps.disabled).toBe(true);
    expect(locationProps.value).toBe(locationData);
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

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    render(
        <Location
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    const locationProps: any = getLatestMockProps((LocationComponent: any));
    locationProps.onChange(newLocation);
    expect(changeSpy).toBeCalledWith(newLocation);
    expect(finishSpy).toBeCalledWith();
});
