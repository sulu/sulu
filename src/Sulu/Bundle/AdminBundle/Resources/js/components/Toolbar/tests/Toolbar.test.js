// @flow
import {render} from 'enzyme';
import React from 'react';
import Button from '../Button';
import Controls from '../Controls';
import Snackbar from '../Snackbar';
import Toolbar from '../Toolbar';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Render controls', () => {
    expect(render(
        <Toolbar>
            <Controls>
                <Button onClick={jest.fn()}>Test</Button>
            </Controls>
            <Controls>
                <Button onClick={jest.fn()}>Test</Button>
            </Controls>
        </Toolbar>
    )).toMatchSnapshot();
});

test('Render with Snackbar', () => {
    expect(render(
        <Toolbar>
            <Snackbar onCloseClick={jest.fn()} />
            <Controls>
                <Button onClick={jest.fn()}>Test</Button>
            </Controls>
        </Toolbar>
    )).toMatchSnapshot();
});

test('Render dark theme', () => {
    expect(render(
        <Toolbar skin="dark">
            <Controls>
                <Button onClick={jest.fn()}>Test</Button>
            </Controls>
            <Controls>
                <Button onClick={jest.fn()}>Test</Button>
            </Controls>
        </Toolbar>
    )).toMatchSnapshot();
});
