// @flow
import React from 'react';
import CountryFieldFilterType from '../../fieldFilterTypes/CountryFieldFilterType';

test('Render with value', () => {
    CountryFieldFilterType.countries = {
        AT: 'Austria',
        DE: 'Germany',
        NL: 'Netherlands',
    };

    const countryFieldFilterType = new CountryFieldFilterType(jest.fn(), {}, undefined);
    const formNode = countryFieldFilterType.getFormNode();
    const children = React.Children.toArray(formNode.props.children);

    expect(children[1].props.children).toHaveLength(3);
});

test('Filter countries using input field', () => {
    CountryFieldFilterType.countries = {
        AT: 'Austria',
        DE: 'Germany',
        NL: 'Netherlands',
    };

    const countryFieldFilterType = new CountryFieldFilterType(jest.fn(), {}, undefined);
    const initialFormNode = countryFieldFilterType.getFormNode();
    const initialChildren = React.Children.toArray(initialFormNode.props.children);

    initialChildren[0].props.onChange('Aus');

    const filteredFormNode = countryFieldFilterType.getFormNode();
    const filteredChildren = React.Children.toArray(filteredFormNode.props.children);
    const checkboxes = React.Children.toArray(filteredChildren[1].props.children);

    expect(checkboxes).toHaveLength(1);
    expect(checkboxes[0].props.value).toEqual('AT');
});

test('Filter countries using input field with lowercase start', () => {
    CountryFieldFilterType.countries = {
        AT: 'Austria',
        DE: 'Germany',
        NL: 'Netherlands',
    };

    const countryFieldFilterType = new CountryFieldFilterType(jest.fn(), {}, undefined);
    const initialFormNode = countryFieldFilterType.getFormNode();
    const initialChildren = React.Children.toArray(initialFormNode.props.children);

    initialChildren[0].props.onChange('aus');

    const filteredFormNode = countryFieldFilterType.getFormNode();
    const filteredChildren = React.Children.toArray(filteredFormNode.props.children);
    const checkboxes = React.Children.toArray(filteredChildren[1].props.children);

    expect(checkboxes).toHaveLength(1);
    expect(checkboxes[0].props.value).toEqual('AT');
});

test.each([
    [['AT'], 'Austria'],
    [['DE', 'NL'], 'Germany, Netherlands'],
    [undefined, null],
    [null, null],
])('Return value node for %s', (value, expectedValueNode) => {
    CountryFieldFilterType.countries = {
        AT: 'Austria',
        DE: 'Germany',
        NL: 'Netherlands',
    };

    const countryFieldFilterType = new CountryFieldFilterType(jest.fn(), {}, null);
    const valueNodePromise = countryFieldFilterType.getValueNode(value);

    return valueNodePromise.then((valueNode) => {
        expect(valueNode).toEqual(expectedValueNode);
    });
});
