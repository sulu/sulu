// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import log from 'loglevel';
import Block from '../Block';

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

test('Render an expanded block with multiple types', () => {
    const {container} = render(
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
        </Block>);

    expect(container).toMatchSnapshot();
});

test('Render an block without handle or collapse or expand button', () => {
    const {container} = render(
        <Block expanded={true}>
            Some block content
        </Block>
    );
    expect(container).toMatchSnapshot();
});

test('Render a selected block', () => {
    expect(render(
        <Block expanded={false} selected={true}>
            Some block content
        </Block>
    )).toMatchSnapshot();
});

test('Render a collapsed block', () => {
    const {container} = render(
        <Block expanded={false} icons={['su-eye', 'su-people']} onCollapse={jest.fn()} onExpand={jest.fn()}>
            Some block content
        </Block>
    );
    expect(container).toMatchSnapshot();
});

test('Do not show type dropdown if only a single type is passed', () => {
    const {container} = render(
        <Block expanded={true} onCollapse={jest.fn()} onExpand={jest.fn()} types={{'type': 'Type'}}>
            Some block content
        </Block>
    );

    // eslint-disable-next-line testing-library/no-container
    const elements = container.getElementsByClassName('select');

    expect(elements).toHaveLength(0);
});

test('Do not show action icon if no actions prop has been passed', () => {
    render(
        <Block expanded={true} onCollapse={jest.fn()} onExpand={jest.fn()} types={{'type': 'Type'}}>
            Some block content
        </Block>
    );

    expect(screen.queryByLabelText('su-more-circle')).not.toBeInTheDocument();
});

test('Do not show action icon if an empty actions prop has been passed', () => {
    render(
        <Block actions={[]} expanded={true} onCollapse={jest.fn()} onExpand={jest.fn()} types={{'type': 'Type'}}>
            Some block content
        </Block>
    );

    expect(screen.queryByLabelText('su-more-circle')).not.toBeInTheDocument();
});

test('Do not show settings icon if no onSettingsClick prop has been passed', () => {
    render(
        <Block expanded={true} onCollapse={jest.fn()} onExpand={jest.fn()} types={{'type': 'Type'}}>
            Some block content
        </Block>
    );

    expect(screen.queryByLabelText('su-cog')).not.toBeInTheDocument();
});

test('Clicking on a collapsed block should call the onExpand callback', async() => {
    const expandSpy = jest.fn();
    render(<Block onCollapse={jest.fn()} onExpand={expandSpy}>Block content</Block>);

    await userEvent.click(screen.queryByRole('switch'));

    expect(expandSpy).toHaveBeenCalledTimes(1);
});

test('Clicking on a expanded block should not call the onExpand callback', async() => {
    const expandSpy = jest.fn();
    render(<Block expanded={true} onCollapse={jest.fn()} onExpand={expandSpy}>Block content</Block>);

    await userEvent.click(screen.queryByRole('switch'));

    expect(expandSpy).not.toBeCalled();
});

test('Clicking the close icon in an expanded block should collapse it', async() => {
    const collapseSpy = jest.fn();
    render(<Block expanded={true} onCollapse={collapseSpy} onExpand={jest.fn()}>Block content</Block>);

    const closeIcon = screen.queryByLabelText('su-collapse-vertical');
    expect(closeIcon).toBeInTheDocument();

    await userEvent.click(closeIcon);

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
    render(
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
    render(
        <Block actions={actions} expanded={true} onCollapse={jest.fn()} onExpand={jest.fn()}>Block content</Block>
    );
    block.find('Icon[name="su-more-circle"]').simulate('click');

    expect(onActionClickSpy).not.toBeCalled();
    block.find('ActionPopover Popover button').at(0).simulate('click');
    expect(onActionClickSpy).toBeCalledWith();
});

test('Render remove action if deprecated onRemove prop is set', async() => {
    const removeSpy = jest.fn();
    render(
        <Block expanded={true} onCollapse={jest.fn()} onExpand={jest.fn()} onRemove={removeSpy}>Block content</Block>
    );
    expect(log.warn).toBeCalledWith(
        expect.stringContaining('The "onRemove" prop of the "Block" component is deprecated')
    );

    const actionIcon = screen.queryByLabelText('su-more-circle');
    expect(actionIcon).toBeInTheDocument();
    await userEvent.click(actionIcon);

    const removeIcon = screen.queryByLabelText('su-trash-alt');
    expect(removeIcon).toBeInTheDocument();
    await userEvent.click(removeIcon);

    expect(removeSpy).toHaveBeenCalledTimes(1);
});

test('Changing the type should call the onTypeChange callback', async() => {
    const typeChangeSpy = jest.fn();
    const types = {
        type1: 'Type 1',
        type2: 'Type 2',
    };

    render(
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

    const selectButton = screen.queryByText('Type 1');
    await userEvent.click(selectButton);

    const typeButton = screen.queryByText('Type 2');
    await userEvent.click(typeButton);

    expect(typeChangeSpy).toBeCalledWith('type2');
    expect(typeChangeSpy).toHaveBeenCalledTimes(1);
});
