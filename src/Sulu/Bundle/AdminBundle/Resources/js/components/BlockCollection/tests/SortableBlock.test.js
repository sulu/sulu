// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import log from 'loglevel';
import SortableBlock from '../SortableBlock';

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

jest.mock('react-sortable-hoc', () => ({
    SortableContainer: jest.fn().mockImplementation((component) => component),
    SortableElement: jest.fn().mockImplementation((component) => component),
    SortableHandle: jest.fn().mockImplementation((component) => component),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

test('Render collapsed sortable block', () => {
    expect(render(
        <SortableBlock
            actions={[]}
            activeType="editor"
            expanded={false}
            icons={['su-eye', 'su-people']}
            onCollapse={jest.fn()}
            onExpand={jest.fn()}
            onRemove={jest.fn()}
            renderBlockContent={jest.fn()}
            selected={false}
            sortIndex={1}
            value={{content: 'Test Content'}}
        />
    )).toMatchSnapshot();
});

test('Render expanded sortable, non-collapsable block with types', () => {
    const renderBlockContent = jest.fn().mockImplementation(
        (value, type) => 'Test for ' + value.content + (type ? ' and type ' + type : '')
    );

    expect(render(
        <SortableBlock
            actions={[]}
            activeType="type2"
            expanded={true}
            onRemove={jest.fn()}
            onSettingsClick={jest.fn()}
            renderBlockContent={renderBlockContent}
            selected={false}
            sortIndex={1}
            types={{type1: 'Type 1', type2: 'Type 2'}}
            value={{content: 'Test Content'}}
        />
    )).toMatchSnapshot();
});

test('Render block in selection mode unselected', () => {
    const renderBlockContent = jest.fn().mockImplementation(
        (value, type) => 'Test for ' + value.content + (type ? ' and type ' + type : '')
    );

    expect(render(
        <SortableBlock
            actions={[]}
            activeType="type2"
            expanded={true}
            mode="selectable"
            onRemove={jest.fn()}
            onSettingsClick={jest.fn()}
            renderBlockContent={renderBlockContent}
            selected={false}
            sortIndex={1}
            types={{type1: 'Type 1', type2: 'Type 2'}}
            value={{content: 'Test Content'}}
        />
    )).toMatchSnapshot();
});

test('Render block in selection mode selected', () => {
    const renderBlockContent = jest.fn().mockImplementation(
        (value, type) => 'Test for ' + value.content + (type ? ' and type ' + type : '')
    );

    expect(render(
        <SortableBlock
            actions={[]}
            activeType="type2"
            expanded={true}
            mode="selectable"
            onRemove={jest.fn()}
            onSettingsClick={jest.fn()}
            renderBlockContent={renderBlockContent}
            selected={true}
            sortIndex={1}
            types={{type1: 'Type 1', type2: 'Type 2'}}
            value={{content: 'Test Content'}}
        />
    )).toMatchSnapshot();
});

test('Should not show block types if only a single block is passed', () => {
    const sortableBlock = mount(
        <SortableBlock
            actions={[]}
            activeType="editor"
            expanded={true}
            onCollapse={jest.fn()}
            onExpand={jest.fn()}
            onRemove={jest.fn()}
            renderBlockContent={jest.fn()}
            selected={false}
            sortIndex={1}
            value={{content: 'Test Content'}}
        />
    );

    expect(sortableBlock.find('SingleSelect')).toHaveLength(0);
});

test('Should apply sortIndex to given actions and pass wrapped actions to Block component', () => {
    const onActionClickSpy = jest.fn();
    const actions = [
        {
            type: 'button',
            icon: 'su-test-1',
            label: 'Test Action 1',
            onClick: onActionClickSpy,
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
    ];

    const sortableBlock = mount(
        <SortableBlock
            actions={actions}
            activeType="editor"
            expanded={true}
            onCollapse={jest.fn()}
            onExpand={jest.fn()}
            renderBlockContent={jest.fn()}
            selected={false}
            sortIndex={101}
            value={{content: 'Test Content'}}
        />
    );

    const blockActions = sortableBlock.find('Block').prop('actions');
    expect(blockActions).toEqual([
        expect.objectContaining({
            type: 'button',
            icon: 'su-test-1',
            label: 'Test Action 1',
        }),
        {
            type: 'divider',
        },
        expect.objectContaining({
            type: 'button',
            icon: 'su-test-2',
            label: 'Test Action 2',
        }),
    ]);

    expect(onActionClickSpy).not.toBeCalled();
    blockActions[0].onClick();
    expect(onActionClickSpy).toBeCalledWith(101);
});

test('Should pass remove action to Block component if depracted onRemove prop is set', () => {
    const removeSpy = jest.fn();
    const actions = [
        {
            type: 'button',
            icon: 'su-test',
            label: 'Test Action',
            onClick: jest.fn(),
        },
    ];

    const sortableBlock = mount(
        <SortableBlock
            actions={actions}
            activeType="editor"
            expanded={true}
            onCollapse={jest.fn()}
            onExpand={jest.fn()}
            onRemove={removeSpy}
            renderBlockContent={jest.fn()}
            selected={false}
            sortIndex={101}
            value={{content: 'Test Content'}}
        />
    );
    expect(log.warn).toBeCalledWith(
        expect.stringContaining('The "onRemove" prop of the "SortableBlock" component is deprecated')
    );

    const blockActions = sortableBlock.find('Block').prop('actions');
    expect(blockActions).toEqual([
        expect.objectContaining({
            type: 'button',
            icon: 'su-test',
            label: 'Test Action',
        }),
        expect.objectContaining({
            type: 'button',
            icon: 'su-trash-alt',
            label: 'sulu_admin.delete',
        }),
    ]);

    expect(removeSpy).not.toBeCalled();
    blockActions[1].onClick();
    expect(removeSpy).toBeCalledWith(101);
});

test('Should not show the settings icon if no onSettingsClick callback is passed', () => {
    const sortableBlock = mount(
        <SortableBlock
            actions={[]}
            activeType="editor"
            expanded={true}
            onCollapse={jest.fn()}
            onExpand={jest.fn()}
            renderBlockContent={jest.fn()}
            selected={false}
            sortIndex={1}
            value={{content: 'Test Content'}}
        />
    );

    expect(sortableBlock.find('Icon[name="su-cog"]')).toHaveLength(0);
});

test('Should call onCollapse when the block is being collapsed', () => {
    const collapseSpy = jest.fn();
    const expandSpy = jest.fn();
    const removeSpy = jest.fn();

    const sortableBlock = shallow(
        <SortableBlock
            actions={[]}
            activeType="editor"
            expanded={true}
            onCollapse={collapseSpy}
            onExpand={expandSpy}
            onRemove={removeSpy}
            renderBlockContent={jest.fn()}
            selected={false}
            sortIndex={1}
            value={{content: 'Test Content'}}
        />
    );

    sortableBlock.find('Block').prop('onCollapse')();

    expect(collapseSpy).toBeCalledWith(1);
    expect(expandSpy).not.toBeCalled();
    expect(removeSpy).not.toBeCalled();
});

test('Should call onExpand when the block is being expanded', () => {
    const collapseSpy = jest.fn();
    const expandSpy = jest.fn();
    const removeSpy = jest.fn();

    const sortableBlock = shallow(
        <SortableBlock
            actions={[]}
            activeType="editor"
            expanded={true}
            onCollapse={collapseSpy}
            onExpand={expandSpy}
            onRemove={removeSpy}
            renderBlockContent={jest.fn()}
            selected={false}
            sortIndex={1}
            value={{content: 'Test Content'}}
        />
    );

    sortableBlock.find('Block').prop('onExpand')();

    expect(collapseSpy).not.toBeCalled();
    expect(expandSpy).toBeCalledWith(1);
    expect(removeSpy).not.toBeCalled();
});

test('Should call onSettingClick when the block setting icon is clicked', () => {
    const collapseSpy = jest.fn();
    const expandSpy = jest.fn();
    const settingsClickSpy = jest.fn();

    const sortableBlock = shallow(
        <SortableBlock
            actions={[]}
            activeType="editor"
            expanded={true}
            onCollapse={collapseSpy}
            onExpand={expandSpy}
            onSettingsClick={settingsClickSpy}
            renderBlockContent={jest.fn()}
            selected={false}
            sortIndex={1}
            value={{content: 'Test Content'}}
        />
    );

    sortableBlock.find('Block').prop('onSettingsClick')();

    expect(collapseSpy).not.toBeCalled();
    expect(expandSpy).not.toBeCalled();
    expect(settingsClickSpy).toBeCalledWith(1);
});

test('Should call onTypeChange when the block has changed its type', () => {
    const typeChangeSpy = jest.fn();

    const sortableBlock = shallow(
        <SortableBlock
            actions={[]}
            activeType="editor"
            expanded={true}
            onCollapse={jest.fn()}
            onExpand={jest.fn()}
            onRemove={jest.fn()}
            onTypeChange={typeChangeSpy}
            renderBlockContent={jest.fn()}
            selected={false}
            sortIndex={1}
            value={{content: 'Test Content'}}
        />
    );

    sortableBlock.find('Block').prop('onTypeChange')('type1');

    expect(typeChangeSpy).toBeCalledWith('type1', 1);
});

test('Should call renderBlockContent with the correct arguments', () => {
    const renderBlockContentSpy = jest.fn();
    const value = {content: 'Test 1'};

    shallow(
        <SortableBlock
            actions={[]}
            activeType="editor"
            expanded={true}
            onCollapse={jest.fn()}
            onExpand={jest.fn()}
            onRemove={jest.fn()}
            renderBlockContent={renderBlockContentSpy}
            selected={false}
            sortIndex={7}
            value={value}
        />
    );

    expect(renderBlockContentSpy).toBeCalledWith(value, 'editor', 7, true);
});

test('Should call renderBlockContent with the correct arguments when block is collapsed', () => {
    const renderBlockContentSpy = jest.fn();
    const value = {content: 'Test 1'};

    shallow(
        <SortableBlock
            actions={[]}
            activeType="editor"
            expanded={false}
            onCollapse={jest.fn()}
            onExpand={jest.fn()}
            onRemove={jest.fn()}
            renderBlockContent={renderBlockContentSpy}
            selected={false}
            sortIndex={7}
            value={value}
        />
    );

    expect(renderBlockContentSpy).toBeCalledWith(value, 'editor', 7, false);
});

test('Should call renderBlockContent with the correct arguments when types are involved', () => {
    const renderBlockContentSpy = jest.fn();
    const value = {content: 'Test 2'};

    shallow(
        <SortableBlock
            actions={[]}
            activeType="test"
            expanded={true}
            onCollapse={jest.fn()}
            onExpand={jest.fn()}
            onRemove={jest.fn()}
            renderBlockContent={renderBlockContentSpy}
            selected={false}
            sortIndex={7}
            value={value}
        />
    );

    expect(renderBlockContentSpy).toBeCalledWith(value, 'test', 7, true);
});
