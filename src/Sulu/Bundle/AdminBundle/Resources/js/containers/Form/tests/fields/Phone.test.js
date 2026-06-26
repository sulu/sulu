// @flow
import React from 'react';
import {render} from '@testing-library/react';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import Phone from '../../fields/Phone';

let mockPhoneProps: Object = {};

const mockReact = require('react');

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());
jest.mock('../../../../components/Phone', () => jest.fn((props) => {
    mockPhoneProps = props;

    return mockReact.createElement('input', {type: 'tel'});
}));

beforeEach(() => {
    mockPhoneProps = {};
});

test('Pass error correctly to component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const error = {keyword: 'minLength', parameters: {}};

    render(
        <Phone
            {...fieldTypeDefaultProps}
            error={error}
            formInspector={formInspector}
        />
    );

    expect(mockPhoneProps.valid).toBe(false);
});

test('Pass props correctly to component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    render(
        <Phone
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
        />
    );

    expect(mockPhoneProps.valid).toBe(true);
    expect(mockPhoneProps.disabled).toBe(true);
});
