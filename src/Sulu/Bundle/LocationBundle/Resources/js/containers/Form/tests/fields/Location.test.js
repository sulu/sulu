// @flow
import {render, screen} from '@testing-library/react';
import React from 'react';
import fieldTypeDefaultProps from 'sulu-admin-bundle/utils/TestHelper/fieldTypeDefaultProps';
import {Location} from '../../../../containers/Form';
import LocationComponent from '../../../../containers/Location/Location';

jest.mock('../../../../containers/Location/Location', () => jest.fn(({disabled, value}) => (
    <div data-testid="location-component">
        {disabled ? 'disabled' : 'enabled'}:{value ? 'with-value' : 'without-value'}
    </div>
)));

beforeEach(() => {
    jest.clearAllMocks();
});

function createFormInspector(locale: any = undefined) {
    return ({locale}: any);
}

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
            formInspector={createFormInspector()}
            value={locationData}
        />
    );

    expect(screen.getByTestId('location-component')).toHaveTextContent('disabled:with-value');
    expect(LocationComponent).toHaveBeenCalledTimes(1);
    const [locationComponentProps] = (LocationComponent: any).mock.calls[0];
    expect(locationComponentProps.disabled).toBe(true);
    expect(locationComponentProps.value).toBe(locationData);
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
            formInspector={createFormInspector()}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    const [locationComponentProps] = (LocationComponent: any).mock.calls[0];
    locationComponentProps.onChange(newLocation);

    expect(changeSpy).toBeCalledWith(newLocation);
    expect(finishSpy).toBeCalledWith();
});
