/* eslint-disable flowtype/require-valid-file-annotation */
import {render} from 'enzyme';
import React from 'react';
import Toolbar from '../Toolbar';
import Controls from '../Controls';
import Button from '../Button';

const clickSpy = jest.fn();

test('Render controls', () => {
    expect(render(
        <Toolbar>
            <Controls>
                <Button onClick={clickSpy}>Test</Button>
            </Controls>
            <Controls>
                <Button onClick={clickSpy}>Test</Button>
            </Controls>
        </Toolbar>
    )).toMatchSnapshot();
});

test('Render dark theme', () => {
    expect(render(
        <Toolbar skin="dark">
            <Controls>
                <Button onClick={clickSpy}>Test</Button>
            </Controls>
            <Controls>
                <Button onClick={clickSpy}>Test</Button>
            </Controls>
        </Toolbar>
    )).toMatchSnapshot();
});
