/* eslint-disable flowtype/require-valid-file-annotation */
import {render} from '@testing-library/react';
import React from 'react';
import Controls from '../Controls';
import Button from '../Button';

const clickSpy = jest.fn();

test('Render controls', () => {
    const {container} = render(<Controls />);
    expect(container).toMatchSnapshot();
});

test('Render controls with children', () => {
    const {container} = render(<Controls><Button onClick={clickSpy}>Test</Button></Controls>);
    expect(container).toMatchSnapshot();
});

test('Render growing controls', () => {
    const {container} = render(<Controls grow={true}></Controls>);
    expect(container).toMatchSnapshot();
});
