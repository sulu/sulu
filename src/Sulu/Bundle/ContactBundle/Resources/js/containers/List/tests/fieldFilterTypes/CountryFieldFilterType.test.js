// @flow
import {mount, render} from 'enzyme';
import CountryFieldFilterType from '../../fieldFilterTypes/CountryFieldFilterType';

test('Render with value', () => {
    CountryFieldFilterType.countries = {
        AT: 'Austria',
        DE: 'Germany',
        NL: 'Netherlands',
    };

    const countryFieldFilterType = new CountryFieldFilterType(jest.fn(), {}, undefined);
    expect(render(countryFieldFilterType.getFormNode())).toMatchSnapshot();
});

test('Filter countries using input field', () => {
    CountryFieldFilterType.countries = {
        AT: 'Austria',
        DE: 'Germany',
        NL: 'Netherlands',
    };

    const countryFieldFilterType = new CountryFieldFilterType(jest.fn(), {}, undefined);
    const countryFieldFilterTypeForm1 = mount(countryFieldFilterType.getFormNode());
    countryFieldFilterTypeForm1.find('Input').prop('onChange')('Aus');

    const countryFieldFilterTypeForm2 = mount(countryFieldFilterType.getFormNode());
    expect(countryFieldFilterTypeForm2.find('Checkbox')).toHaveLength(1);
    expect(countryFieldFilterTypeForm2.find('Checkbox').at(0).prop('value')).toEqual('AT');
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
