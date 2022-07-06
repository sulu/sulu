// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import log from 'loglevel';
import Block from '../Block';

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

test('Render an expanded block with a multiple types', () => {
    expect(render(
        <Block
            activeType="type1"
            expanded={true}
            handle={<span>Test</span>}
            icons={['su-eye', 'su-people']}
            onCollapse={jest.fn()}
            onExpand={jest.fn()}
            onSettingsClick={jest.fn()}
            types={{'type1': 'Type1', 'type2': 'Type2'}}
        >
            Some block content
        </Block>
    )).toMatchSnapshot();
});

test('Render an block without handle or collapse or expand button', () => {
    expect(render(
        <Block expanded={true}>
            Some block content
        </Block>
    )).toMatchSnapshot();
});

test('Render a selected block', () => {
    expect(render(
        <Block expanded={false} selected={true}>
            Some block content
        </Block>
    )).toMatchSnapshot();
});

test('Render a collapsed block', () => {
    expect(render(
        <Block expanded={false} icons={['su-eye', 'su-people']} onCollapse={jest.fn()} onExpand={jest.fn()}>
            Some block content
        </Block>
    )).toMatchSnapshot();
});

test('Do not show type dropdown if only a single type is passed', () => {
    const block = shallow(
        <Block expanded={true} onCollapse={jest.fn()} onExpand={jest.fn()} types={{'type': 'Type'}}>
            Some block content
        </Block>
    );

    expect(block.find('SingleSelect')).toHaveLength(0);
});

test('Do not show action icon if no actions prop has been passed', () => {
    const block = shallow(
        <Block expanded={true} onCollapse={jest.fn()} onExpand={jest.fn()} types={{'type': 'Type'}}>
            Some block content
        </Block>
    );

    expect(block.find('Icon[name="su-more-circle"]')).toHaveLength(0);
});

test('Do not show action icon if an empty actions prop has been passed', () => {
    const block = shallow(
        <Block actions={[]} expanded={true} onCollapse={jest.fn()} onExpand={jest.fn()} types={{'type': 'Type'}}>
            Some block content
        </Block>
    );

    expect(block.find('Icon[name="su-more-circle"]')).toHaveLength(0);
});

test('Do not show settings icon if no onSettingsClick prop has been passed', () => {
    const block = shallow(
        <Block expanded={true} onCollapse={jest.fn()} onExpand={jest.fn()} types={{'type': 'Type'}}>
            Some block content
        </Block>
    );

    expect(block.find('Icon[name="su-cog"]')).toHaveLength(0);
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
    const block = mount(<Block expanded={true} onCollapse={collapseSpy} onExpand={jest.fn()}>Block content</Block>);

    const closeIcon = block.find('Icon[name="su-collapse-vertical"]');
    expect(closeIcon).toHaveLength(1);

    closeIcon.simulate('click');

    expect(collapseSpy).toHaveBeenCalledTimes(1);
});

test('Clicking the action icon should open a popover that displays the given actions', () => {
    const actions = [
        {
            type: 'button',
            icon: 'su-test-1',
            label: 'Test Action 1',
            onClick: jest.fn(),
        },
        {
            type: 'divider',
        },
        {
            type: 'button',
            icon: 'su-test-2',
            label: 'Test Action 2',
            onClick: jest.fn(),
        },
        {
            type: 'button',
            icon: 'su-test-3',
            label: 'Test Action 3',
            onClick: jest.fn(),
        },
    ];
    const block = mount(
        <Block actions={actions} expanded={true} onCollapse={jest.fn()} onExpand={jest.fn()}>Block content</Block>
    );
    expect(block.find('ActionPopover').prop('open')).toEqual(false);
    expect(block.find('Icon[name="su-more-circle"]')).toHaveLength(1);
    block.find('Icon[name="su-more-circle"]').simulate('click');

    expect(block.find('ActionPopover').prop('open')).toEqual(true);
    expect(block.find('ActionPopover Popover').render()).toMatchSnapshot();
});

test('Clicking an action in the action popover should fire the respective callback', () => {
    const onActionClickSpy = jest.fn();
    const actions = [
        {
            type: 'button',
            icon: 'su-test-1',
            label: 'Test Action 1',
            onClick: onActionClickSpy,
        },
    ];
    const block = mount(
        <Block actions={actions} expanded={true} onCollapse={jest.fn()} onExpand={jest.fn()}>Block content</Block>
    );
    block.find('Icon[name="su-more-circle"]').simulate('click');

    expect(onActionClickSpy).not.toBeCalled();
    block.find('ActionPopover Popover button').at(0).simulate('click');
    expect(onActionClickSpy).toBeCalledWith();
});

test('Render remove action if deprecated onRemove prop is set', () => {
    const removeSpy = jest.fn();
    const block = mount(
        <Block expanded={true} onCollapse={jest.fn()} onExpand={jest.fn()} onRemove={removeSpy}>Block content</Block>
    );
    expect(log.warn).toBeCalledWith(
        expect.stringContaining('The "onRemove" prop of the "Block" component is deprecated')
    );

    expect(block.find('Icon[name="su-more-circle"]')).toHaveLength(1);
    block.find('Icon[name="su-more-circle"]').simulate('click');

    expect(block.find('Icon[name="su-trash-alt"]')).toHaveLength(1);
    block.find('Icon[name="su-trash-alt"]').simulate('click');

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
