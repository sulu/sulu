// @flow
import {mount} from 'enzyme';
import React from 'react';
import Search from '../Search';

jest.mock('../../../utils/Translator', () => ({
    translate: function(key) {
        return key;
    },
}));

test('The component should render collapsed', () => {
    const search = mount(
        <Search onSearch={jest.fn()} />
    );

    expect(search.render()).toMatchSnapshot();
});

test('The component should expand the input when clicking on icon', () => {
    const search = mount(
        <Search onSearch={jest.fn()} />
    );

    search.find('Icon').simulate('click');

    expect(search.render()).toMatchSnapshot();
});
