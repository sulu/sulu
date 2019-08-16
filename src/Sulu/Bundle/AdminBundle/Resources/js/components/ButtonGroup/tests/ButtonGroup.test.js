// @flow
import React from 'react';
import {render} from 'enzyme';
import ButtonGroup from '../ButtonGroup';
import Button from '../../Button';
import DropdownButton from '../../DropdownButton';
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

test('Should render a button and a dropdown button', () => {
    const handleClick = jest.fn();

    const buttonGroup = (
        <ButtonGroup>
            <Button onClick={handleClick}><Icon name="su-th-large" /></Button>
            <DropdownButton>
                <DropdownButton.Item onClick={jest.fn()}>Test</DropdownButton.Item>
            </DropdownButton>
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

test('Should render a button with a custom className', () => {
    const handleClick = jest.fn();

    const buttonGroup = (
        <ButtonGroup>
            <Button className="test" onClick={handleClick}><Icon name="su-th-large" /></Button>
        </ButtonGroup>
    );
    expect(render(buttonGroup)).toMatchSnapshot();
});

test('Should render one button with a custom className and another one without', () => {
    const handleClick = jest.fn();

    const buttonGroup = (
        <ButtonGroup>
            <Button className="test" onClick={handleClick}><Icon name="su-th-large" /></Button>
            <Button onClick={handleClick}><Icon name="su-align-justify" /></Button>
        </ButtonGroup>
    );
    expect(render(buttonGroup)).toMatchSnapshot();
});
