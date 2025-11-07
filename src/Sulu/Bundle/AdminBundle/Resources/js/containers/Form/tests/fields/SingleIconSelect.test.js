// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {observable} from 'mobx';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import {SingleIconSelection} from '../../index';
import SingleItemSelection from '../../../../components/SingleItemSelection';
import SingleListOverlay from '../../../SingleListOverlay';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

test('Pass props correctly to SingleIconSelect', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const schemaOptions = observable({
        icon_set: {
            value: 'website',
        },
    });
    const singleIconSelection = shallow(
        <SingleIconSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value="test"
        />
    );

    expect(singleIconSelection.find(SingleItemSelection).prop('value')).toBe('test');
    expect(singleIconSelection.find(SingleListOverlay).prop('options')).toEqual({icon_set: 'website'});
});

test('Pass undefined as icon_set to SingleIconSelect', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const schemaOptions = observable({});

    expect(() => shallow(
        <SingleIconSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    )).toThrow(/"icon_set"/);
});
