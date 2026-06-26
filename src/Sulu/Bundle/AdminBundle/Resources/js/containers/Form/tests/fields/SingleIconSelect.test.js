// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {observable} from 'mobx';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import {SingleIconSelection} from '../../index';

let mockSingleItemSelectionProps: Object = {};
let mockSingleListOverlayProps: Object = {};

const mockReact = require('react');

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());
jest.mock('../../../../components/SingleItemSelection', () => jest.fn((props) => {
    mockSingleItemSelectionProps = props;

    return mockReact.createElement('div', null, props.children);
}));
jest.mock('../../../SingleListOverlay', () => jest.fn((props) => {
    mockSingleListOverlayProps = props;

    return mockReact.createElement('div');
}));

beforeEach(() => {
    mockSingleItemSelectionProps = {};
    mockSingleListOverlayProps = {};
});

test('Pass props correctly to SingleIconSelect', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const schemaOptions = observable({
        icon_set: {
            value: 'website',
        },
    });
    render(
        <SingleIconSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value="test"
        />
    );

    expect(mockSingleItemSelectionProps.value).toBe('test');
    expect(mockSingleListOverlayProps.options).toEqual({icon_set: 'website'});
});

test('Pass undefined as icon_set to SingleIconSelect', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const schemaOptions = observable({});

    const singleIconSelection = new SingleIconSelection(({
        ...fieldTypeDefaultProps,
        formInspector,
        schemaOptions,
    }: any));

    expect(() => singleIconSelection.render()).toThrow(/"icon_set"/);
});
