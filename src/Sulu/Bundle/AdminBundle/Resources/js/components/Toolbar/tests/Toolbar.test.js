// @flow
import {render} from '@testing-library/react';
import React from 'react';
import Button from '../Button';
import Controls from '../Controls';
import Toolbar from '../Toolbar';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Render controls', () => {
    const {container} = render(
        <Toolbar>
            <Controls>
                <Button onClick={jest.fn()}>Test</Button>
            </Controls>
            <Controls>
                <Button onClick={jest.fn()}>Test</Button>
            </Controls>
        </Toolbar>
    );
    expect(container).toMatchSnapshot();
});

test('Render dark theme', () => {
    const {container} = render(
        <Toolbar skin="dark">
            <Controls>
                <Button onClick={jest.fn()}>Test</Button>
            </Controls>
            <Controls>
                <Button onClick={jest.fn()}>Test</Button>
            </Controls>
        </Toolbar>
    );
    expect(container).toMatchSnapshot();
});
