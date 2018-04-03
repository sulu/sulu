// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import ButtonGroup from '../ButtonGroup';
import Button from '../../Button';
import Icon from '../../Icon';

test('Should render one button', () => {
    const buttonGroup = (
        <ButtonGroup>
            <Button><Icon name="su-view"/></Button>
        </ButtonGroup>
    )
    expect(render(buttonGroup)).toMatchSnapshot();
});

test('Should render two buttons', () => {
    const buttonGroup = (
        <ButtonGroup>
            <Button><Icon name="su-view"/></Button>
            <Button><Icon name="su-view2"/></Button>
        </ButtonGroup>
    )
    expect(render(buttonGroup)).toMatchSnapshot();
});

test('Should render more than two buttons', () => {
    const buttonGroup = (
        <ButtonGroup>
            <Button><Icon name="su-view"/></Button>
            <Button><Icon name="su-view2"/></Button>
            <Button><Icon name="su-view"/></Button>
            <Button><Icon name="su-view2"/></Button>
        </ButtonGroup>
    )
    expect(render(buttonGroup)).toMatchSnapshot();
});