// @flow
import React from 'react';
import {shallow} from 'enzyme';
import fieldTypeDefaultProps from 'sulu-admin-bundle/utils/TestHelper/fieldTypeDefaultProps';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import ResourceFormStore from 'sulu-admin-bundle/containers/Form/stores/ResourceFormStore';
import {FormInspector} from 'sulu-admin-bundle/containers/Form';
import {Location} from '../../../../containers/Form';
import LocationComponent from '../../../../containers/Location/Location';

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn());
jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn());
jest.mock('sulu-admin-bundle/containers/Form/FormInspector', () => jest.fn());

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
    const location = shallow(
        <Location
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value={locationData}
        />
    );

    expect(location.find(LocationComponent).props().disabled).toBe(true);
    expect(location.find(LocationComponent).props().value).toBe(locationData);
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

    const location = shallow(
        <Location
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    location.find(LocationComponent).props().onChange(newLocation);
    expect(changeSpy).toBeCalledWith(newLocation);
    expect(finishSpy).toBeCalledWith();
});
