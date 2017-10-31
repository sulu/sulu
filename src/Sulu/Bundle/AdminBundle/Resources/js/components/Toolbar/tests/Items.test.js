/* eslint-disable flowtype/require-valid-file-annotation */
import {render} from 'enzyme';
import React from 'react';
import Items from '../Items';
import Button from '../Button';

const clickSpy = jest.fn();

test('Render items', () => {
    expect(render(<Items />)).toMatchSnapshot();
});

test('Render items with children', () => {
    expect(render(<Items><Button onClick={clickSpy}>Test</Button></Items>)).toMatchSnapshot();
});
