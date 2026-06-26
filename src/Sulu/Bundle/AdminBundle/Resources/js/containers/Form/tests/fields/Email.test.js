// @flow
import React from 'react';
import {render} from '@testing-library/react';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import Email from '../../fields/Email';

let mockEmailProps: Object = {};

const mockReact = require('react');

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());
jest.mock('../../../../components/Email', () => jest.fn((props) => {
    mockEmailProps = props;

    return mockReact.createElement('input', {type: 'email'});
}));

beforeEach(() => {
    mockEmailProps = {};
});

test('Pass error correctly to component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const error = {keyword: 'minLength', parameters: {}};

    render(
        <Email
            {...fieldTypeDefaultProps}
            error={error}
            formInspector={formInspector}
        />
    );

    expect(mockEmailProps.valid).toBe(false);
});

test('Pass props correctly to component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    render(
        <Email
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
        />
    );

    expect(mockEmailProps.valid).toBe(true);
    expect(mockEmailProps.disabled).toBe(true);
});
