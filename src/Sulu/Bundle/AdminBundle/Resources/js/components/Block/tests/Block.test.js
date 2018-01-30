// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import Block from '../Block';

test('Render an expanded block', () => {
    expect(render(
        <Block expanded={true} onCollapse={jest.fn()} onExpand={jest.fn()}>
            Some block content
        </Block>
    )).toMatchSnapshot();
});

test('Render a collapsed block', () => {
    expect(render(
        <Block expanded={false} onCollapse={jest.fn()} onExpand={jest.fn()}>
            Some block content
        </Block>
    )).toMatchSnapshot();
});

test('Render the dragHandle prop if passed', () => {
    expect(render(
        <Block onCollapse={jest.fn()} onExpand={jest.fn()} dragHandle={<span>Test</span>}>
            Block Content with custom drag handle
        </Block>
    )).toMatchSnapshot();
});

test('Clicking on a collapsed block should call the onExpand callback', () => {
    const expandSpy = jest.fn();
    const block = shallow(<Block onCollapse={jest.fn()} onExpand={expandSpy}>Block content</Block>);

    block.find('section').simulate('click');

    expect(expandSpy).toHaveBeenCalledTimes(1);
});

test('Clicking on a expanded block should not call the onExpand callback', () => {
    const expandSpy = jest.fn();
    const block = shallow(<Block expanded={true} onCollapse={jest.fn()} onExpand={expandSpy}>Block content</Block>);

    block.find('section').simulate('click');

    expect(expandSpy).not.toBeCalled();
});

test('Clicking the close icon in an expanded block should collapse it', () => {
    const collapseSpy = jest.fn();
    const block = shallow(<Block expanded={true} onCollapse={collapseSpy} onExpand={jest.fn()}>Block content</Block>);

    const closeIcon = block.find('Icon[name="times"]');
    expect(closeIcon).toHaveLength(1);

    closeIcon.simulate('click');

    expect(collapseSpy).toHaveBeenCalledTimes(1);
});
