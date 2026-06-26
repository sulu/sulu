// @flow
import React from 'react';
import {render} from '@testing-library/react';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import PasswordConfirmation from '../../fields/PasswordConfirmation';

let mockPasswordConfirmationProps: Object = {};

const mockReact = require('react');

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());
jest.mock('../../../../components/PasswordConfirmation', () => jest.fn((props) => {
    mockPasswordConfirmationProps = props;

    return mockReact.createElement('input', {type: 'password'});
}));

beforeEach(() => {
    mockPasswordConfirmationProps = {};
});

test('Pass error correctly to PasswordConfirmation component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const error = {keyword: 'required', parameters: {}};

    render(
        <PasswordConfirmation
            {...fieldTypeDefaultProps}
            error={error}
            formInspector={formInspector}
        />
    );

    expect(mockPasswordConfirmationProps.valid).toBe(false);
});

test('Pass props correctly to PasswordConfirmation component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    render(
        <PasswordConfirmation
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    expect(mockPasswordConfirmationProps.valid).toBe(true);
    expect(mockPasswordConfirmationProps.disabled).toBe(true);

    mockPasswordConfirmationProps.onChange('value');

    expect(changeSpy).toHaveBeenCalledWith('value');
    expect(finishSpy).toHaveBeenCalledWith();
});
