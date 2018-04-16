// @flow
import React from 'react';
import {render} from 'enzyme';
import ButtonGroup from '../ButtonGroup';
import Button from '../../Button';
import Icon from '../../Icon';

test('Should render one button', () => {
    const handleClick = jest.fn();

    const buttonGroup = (
        <ButtonGroup>
            <Button onClick={handleClick}><Icon name="su-th-large" /></Button>
        </ButtonGroup>
    );
    expect(render(buttonGroup)).toMatchSnapshot();
});

test('Should render two buttons', () => {
    const handleClick = jest.fn();

    const buttonGroup = (
        <ButtonGroup>
            <Button onClick={handleClick}><Icon name="su-th-large" /></Button>
            <Button onClick={handleClick}><Icon name="su-align-justify" /></Button>
        </ButtonGroup>
    );
    expect(render(buttonGroup)).toMatchSnapshot();
});

test('Should render more than two buttons', () => {
    const handleClick = jest.fn();

    const buttonGroup = (
        <ButtonGroup>
            <Button onClick={handleClick}><Icon name="su-th-large" /></Button>
            <Button onClick={handleClick}><Icon name="su-align-justify" /></Button>
            <Button onClick={handleClick}><Icon name="su-th-large" /></Button>
            <Button onClick={handleClick}><Icon name="su-align-justify" /></Button>
        </ButtonGroup>
    );
    expect(render(buttonGroup)).toMatchSnapshot();
});
