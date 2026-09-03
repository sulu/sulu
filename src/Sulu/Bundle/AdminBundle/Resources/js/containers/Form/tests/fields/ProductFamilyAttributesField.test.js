// @flow
import React from 'react';
import {observable} from 'mobx';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import ProductFamilyAttributesField from '../../fields/ProductFamilyAttributesField';

const NEXT_VALUE = [{id: 'a1', required: true, variantSpecific: true}];

// Stand in for the container: renders what it received, plus a button that fires onChange.
jest.mock('../../../Product/ProductFamilyAttributes', () => function ProductFamilyAttributes(props) {
    const React = require('react');

    return React.createElement(
        'div',
        {'data-testid': 'container'},
        JSON.stringify({disabled: props.disabled, locale: props.locale, value: props.value}),
        React.createElement(
            'button',
            {onClick: () => props.onChange(NEXT_VALUE), type: 'button'},
            'change'
        )
    );
});

jest.mock('../../FormInspector', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../../../stores/ResourceStore', () => jest.fn());

test('passes value, disabled and locale through to the container', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'attributes'));
    // $FlowFixMe
    formInspector.locale = observable.box('en');

    const value = [{id: 'a1', required: true, variantSpecific: false}];

    render(
        <ProductFamilyAttributesField
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(screen.getByTestId('container')).toHaveTextContent('"disabled":true');
    expect(screen.getByTestId('container')).toHaveTextContent('"id":"a1"');
});

test('calls onChange and onFinish when the container changes the value', async() => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'attributes'));
    // $FlowFixMe
    formInspector.locale = observable.box('en');

    const handleChange = jest.fn();
    const handleFinish = jest.fn();

    render(
        <ProductFamilyAttributesField
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={handleChange}
            onFinish={handleFinish}
            value={[]}
        />
    );

    await userEvent.click(screen.getByText('change'));

    expect(handleChange).toHaveBeenCalledWith([{id: 'a1', required: true, variantSpecific: true}]);
    expect(handleFinish).toHaveBeenCalled();
});
