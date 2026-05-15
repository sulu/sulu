// @flow
import React from 'react';
import CountryFieldFilterType from '../../fieldFilterTypes/CountryFieldFilterType';

function getFormChildren(fieldFilterType: CountryFieldFilterType) {
    const formNode = fieldFilterType.getFormNode();
    const children = React.Children.toArray(formNode.props.children);

    return {
        checkboxGroupNode: children[1],
        inputNode: children[0],
    };
}

function getCheckboxValues(fieldFilterType: CountryFieldFilterType) {
    const {checkboxGroupNode} = getFormChildren(fieldFilterType);

    if (!React.isValidElement(checkboxGroupNode)) {
        throw new Error('Expected checkbox group node');
    }

    const checkboxes = React.Children.toArray(checkboxGroupNode.props.children);
    return checkboxes.map((checkbox) => {
        if (!React.isValidElement(checkbox)) {
            throw new Error('Expected checkbox element');
        }

        return checkbox.props.value;
    });
}

test('Render with value', () => {
    CountryFieldFilterType.countries = {
        AT: 'Austria',
        DE: 'Germany',
        NL: 'Netherlands',
    };

    const countryFieldFilterType = new CountryFieldFilterType(jest.fn(), {}, undefined);
    expect(getCheckboxValues(countryFieldFilterType)).toEqual(['AT', 'DE', 'NL']);
});

test('Filter countries using input field', () => {
    CountryFieldFilterType.countries = {
        AT: 'Austria',
        DE: 'Germany',
        NL: 'Netherlands',
    };

    const countryFieldFilterType = new CountryFieldFilterType(jest.fn(), {}, undefined);
    const {inputNode} = getFormChildren(countryFieldFilterType);

    if (!React.isValidElement(inputNode)) {
        throw new Error('Expected input node');
    }

    inputNode.props.onChange('Aus');
    expect(getCheckboxValues(countryFieldFilterType)).toEqual(['AT']);
});

test('Filter countries using input field with lowercase start', () => {
    CountryFieldFilterType.countries = {
        AT: 'Austria',
        DE: 'Germany',
        NL: 'Netherlands',
    };

    const countryFieldFilterType = new CountryFieldFilterType(jest.fn(), {}, undefined);
    const {inputNode} = getFormChildren(countryFieldFilterType);

    if (!React.isValidElement(inputNode)) {
        throw new Error('Expected input node');
    }

    inputNode.props.onChange('aus');
    expect(getCheckboxValues(countryFieldFilterType)).toEqual(['AT']);
});

test.each([
    [['AT'], 'Austria'],
    [['DE', 'NL'], 'Germany, Netherlands'],
    [undefined, null],
    [null, null],
])('Return value node for %s', async(value, expectedValueNode) => {
    CountryFieldFilterType.countries = {
        AT: 'Austria',
        DE: 'Germany',
        NL: 'Netherlands',
    };

    const countryFieldFilterType = new CountryFieldFilterType(jest.fn(), {}, null);
    const valueNode = await countryFieldFilterType.getValueNode(value);

    expect(valueNode).toEqual(expectedValueNode);
});
