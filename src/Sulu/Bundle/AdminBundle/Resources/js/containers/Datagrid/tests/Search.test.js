// @flow
import {render, shallow} from 'enzyme';
import React from 'react';
import Search from '../Search';

jest.mock('../../../utils/Translator', () => ({
    translate: function(key) {
        return key;
    },
}));

test('The component should render collapsed', () => {
    const search = render(
        <Search onSearch={jest.fn()} />
    );

    expect(search).toMatchSnapshot();
});

test('The component should expand the input when clicking on icon', () => {
    const search = shallow(
        <Search onSearch={jest.fn()} />
    );

    search.find('Input').simulate('iconClick');

    expect(search).toMatchSnapshot();
});

test('The component should trigger the onSearch callback correctly if Input calls onBlur', () => {
    const onSearch = jest.fn();
    const search = shallow(
        <Search onSearch={onSearch} />
    );

    const input = search.find('Input');
    input.simulate('iconClick');
    input.simulate('change', 'test-search-value');

    expect(search.instance().value).toBe('test-search-value');

    input.simulate('blur');

    expect(onSearch).toBeCalledWith('test-search-value');
});

test('The component should trigger the onSearch callback correctly if Input calls onKeyPress with enter', () => {
    const onSearch = jest.fn();
    const search = shallow(
        <Search onSearch={onSearch} />
    );

    const input = search.find('Input');
    input.simulate('iconClick');
    input.simulate('change', 'test-search-value');

    expect(search.instance().value).toBe('test-search-value');

    input.simulate('keyPress', 'Enter');

    expect(onSearch).toBeCalledWith('test-search-value');
});

test('The component should clear the current value if Input calls onClearClick', () => {
    const onSearch = jest.fn();
    const search = shallow(
        <Search onSearch={onSearch} />
    );

    const input = search.find('Input');
    input.simulate('iconClick');
    input.simulate('change', 'test-search-value');

    expect(search.instance().value).toBe('test-search-value');

    input.simulate('clearClick');

    expect(search.instance().value).toBe(undefined);
    expect(search.instance().collapsed).toBe(true);

    expect(onSearch).toHaveBeenCalledWith(undefined);
});
