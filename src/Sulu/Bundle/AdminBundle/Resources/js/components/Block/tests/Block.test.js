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

test('Render an expanded block with a single type', () => {
    expect(render(
        <Block expanded={true} onCollapse={jest.fn()} onExpand={jest.fn()} types={{'type': 'Type'}}>
            Some block content
        </Block>
    )).toMatchSnapshot();
});

test('Render an expanded block with a multiple types', () => {
    expect(render(
        <Block
            activeType={'type1'}
            expanded={true}
            onCollapse={jest.fn()}
            onExpand={jest.fn()}
            types={{'type1': 'Type1', 'type2': 'Type2'}}
        >
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

test('Passing a onRemove callback should render the remove icon', () => {
    expect(render(
        <Block expanded={true} onCollapse={jest.fn()} onExpand={jest.fn()} onRemove={jest.fn()}>
            Some block content
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

    const closeIcon = block.find('Icon[name="su-times"]');
    expect(closeIcon).toHaveLength(1);

    closeIcon.simulate('click');

    expect(collapseSpy).toHaveBeenCalledTimes(1);
});

test('Clicking the remove icon in an expanded block should remove it', () => {
    const removeSpy = jest.fn();
    const block = shallow(
        <Block expanded={true} onCollapse={jest.fn()} onExpand={jest.fn()} onRemove={removeSpy}>Block content</Block>
    );

    const removeIcon = block.find('Icon[name="su-trash-alt"]');
    expect(removeIcon).toHaveLength(1);

    removeIcon.simulate('click');

    expect(removeSpy).toHaveBeenCalledTimes(1);
});

test('Changing the type should call the onTypeChange callback', () => {
    const typeChangeSpy = jest.fn();
    const types = {
        type1: 'Type 1',
        type2: 'Type 2',
    };
    const block = shallow(
        <Block
            activeType="type1"
            expanded={true}
            onCollapse={jest.fn()}
            onExpand={jest.fn()}
            onTypeChange={typeChangeSpy}
            types={types}
        >
            Block content
        </Block>
    );

    block.find('SingleSelect').simulate('change', 'type2');

    expect(typeChangeSpy).toBeCalledWith('type2');
});
