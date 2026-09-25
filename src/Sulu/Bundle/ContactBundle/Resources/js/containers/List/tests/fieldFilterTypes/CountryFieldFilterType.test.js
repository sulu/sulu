// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import CountryFieldFilterType from '../../fieldFilterTypes/CountryFieldFilterType';

async function typeSearchValue(user, countryFieldFilterType, rerender, value) {
    for (const character of value) {
        await user.type(screen.getByRole('textbox'), character);
        rerender(countryFieldFilterType.getFormNode());
    }
}

test('Render with value', () => {
    CountryFieldFilterType.countries = {
        AT: 'Austria',
        DE: 'Germany',
        NL: 'Netherlands',
    };

    const countryFieldFilterType = new CountryFieldFilterType(jest.fn(), {}, undefined);
    const {asFragment} = render(countryFieldFilterType.getFormNode());
    expect(asFragment()).toMatchSnapshot();
});

test('Filter countries using input field', async() => {
    const user = userEvent.setup();

    CountryFieldFilterType.countries = {
        AT: 'Austria',
        DE: 'Germany',
        NL: 'Netherlands',
    };

    const countryFieldFilterType = new CountryFieldFilterType(jest.fn(), {}, undefined);
    const {rerender} = render(countryFieldFilterType.getFormNode());

    await typeSearchValue(user, countryFieldFilterType, rerender, 'Aus');

    expect(screen.getAllByRole('checkbox')).toHaveLength(1);
    expect(screen.getByDisplayValue('AT')).toBeInTheDocument();
    expect(screen.getByText('Austria')).toBeInTheDocument();
});

test('Filter countries using input field with lowercase start', async() => {
    const user = userEvent.setup();

    CountryFieldFilterType.countries = {
        AT: 'Austria',
        DE: 'Germany',
        NL: 'Netherlands',
    };

    const countryFieldFilterType = new CountryFieldFilterType(jest.fn(), {}, undefined);
    const {rerender} = render(countryFieldFilterType.getFormNode());

    await typeSearchValue(user, countryFieldFilterType, rerender, 'aus');

    expect(screen.getAllByRole('checkbox')).toHaveLength(1);
    expect(screen.getByDisplayValue('AT')).toBeInTheDocument();
    expect(screen.getByText('Austria')).toBeInTheDocument();
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
