// @flow
import {render} from 'enzyme';
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
