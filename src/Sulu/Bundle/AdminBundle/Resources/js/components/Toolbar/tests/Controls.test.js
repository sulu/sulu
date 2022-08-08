/* eslint-disable flowtype/require-valid-file-annotation */
import {render} from 'enzyme';
import React from 'react';
import Controls from '../Controls';
import Button from '../Button';

const clickSpy = jest.fn();

test('Render controls', () => {
    expect(render(<Controls />)).toMatchSnapshot();
});

test('Render controls with children', () => {
    expect(render(<Controls><Button onClick={clickSpy}>Test</Button></Controls>)).toMatchSnapshot();
});

test('Render growing controls', () => {
    expect(render(<Controls grow={true}></Controls>)).toMatchSnapshot();
});
