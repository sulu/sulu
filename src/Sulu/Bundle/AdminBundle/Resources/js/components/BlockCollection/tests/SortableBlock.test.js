// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import log from 'loglevel';
import SortableBlock from '../SortableBlock';

jest.mock('react-sortable-hoc', () => ({
    SortableContainer: jest.fn().mockImplementation((component) => component),
    SortableElement: jest.fn().mockImplementation((component) => component),
    SortableHandle: jest.fn().mockImplementation((component) => component),
}));

test('Render collapsed sortable block', () => {
    const {asFragment} = render(
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
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render expanded sortable, non-collapsable block with types', () => {
    const renderBlockContent = jest.fn().mockImplementation(
        (value, type) => 'Test for ' + value.content + (type ? ' and type ' + type : '')
    );

    const {asFragment} = render(
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
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render block in selection mode unselected', () => {
    const renderBlockContent = jest.fn().mockImplementation(
        (value, type) => 'Test for ' + value.content + (type ? ' and type ' + type : '')
    );

    const {asFragment} = render(
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
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render block in selection mode selected', () => {
    const renderBlockContent = jest.fn().mockImplementation(
        (value, type) => 'Test for ' + value.content + (type ? ' and type ' + type : '')
    );

    const {asFragment} = render(
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
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Should not show block types if only a single block is passed', () => {
    render(
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

    expect(screen.queryByRole('button', {name: /Editor/})).not.toBeInTheDocument();
});

test('Should apply sortIndex to given actions and pass wrapped actions to Block component', async() => {
    const user = userEvent.setup();
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

    render(
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

    expect(onActionClickSpy).not.toBeCalled();
    await user.click(screen.getByLabelText('su-more-circle'));
    await user.click(screen.getByRole('button', {name: /Test Action 1/}));
    expect(onActionClickSpy).toBeCalledWith(101);
});

test('Should pass remove action to Block component if depracted onRemove prop is set', async() => {
    const user = userEvent.setup();
    const removeSpy = jest.fn();
    const actions = [
        {
            type: 'button',
            icon: 'su-test',
            label: 'Test Action',
            onClick: jest.fn(),
        },
    ];

    render(
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

    expect(removeSpy).not.toBeCalled();
    await user.click(screen.getByLabelText('su-more-circle'));
    await user.click(screen.getByRole('button', {name: /sulu_admin\.delete/}));
    expect(removeSpy).toBeCalledWith(101);
});

test('Should not show the settings icon if no onSettingsClick callback is passed', () => {
    render(
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

    expect(screen.queryByLabelText('su-cog')).not.toBeInTheDocument();
});

test('Should call onCollapse when the block is being collapsed', async() => {
    const user = userEvent.setup();
    const collapseSpy = jest.fn();
    const expandSpy = jest.fn();
    const removeSpy = jest.fn();

    render(
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

    await user.click(screen.getByLabelText('su-collapse-vertical'));

    expect(collapseSpy).toBeCalledWith(1);
    expect(expandSpy).not.toBeCalled();
    expect(removeSpy).not.toBeCalled();
});

test('Should call onExpand when the block is being expanded', async() => {
    const user = userEvent.setup();
    const collapseSpy = jest.fn();
    const expandSpy = jest.fn();
    const removeSpy = jest.fn();

    render(
        <SortableBlock
            actions={[]}
            activeType="editor"
            expanded={false}
            onCollapse={collapseSpy}
            onExpand={expandSpy}
            onRemove={removeSpy}
            renderBlockContent={jest.fn()}
            selected={false}
            sortIndex={1}
            value={{content: 'Test Content'}}
        />
    );

    await user.click(screen.getByRole('switch'));

    expect(collapseSpy).not.toBeCalled();
    expect(expandSpy).toBeCalledWith(1);
    expect(removeSpy).not.toBeCalled();
});

test('Should call onSettingClick when the block setting icon is clicked', async() => {
    const user = userEvent.setup();
    const collapseSpy = jest.fn();
    const expandSpy = jest.fn();
    const settingsClickSpy = jest.fn();

    render(
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

    await user.click(screen.getByLabelText('su-cog'));

    expect(collapseSpy).not.toBeCalled();
    expect(expandSpy).not.toBeCalled();
    expect(settingsClickSpy).toBeCalledWith(1);
});

test('Should call onTypeChange when the block has changed its type', async() => {
    const user = userEvent.setup();
    const typeChangeSpy = jest.fn();

    render(
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
            types={{editor: 'Editor', type1: 'Type 1'}}
            value={{content: 'Test Content'}}
        />
    );

    await user.click(screen.getByRole('button', {name: /Editor/}));
    await user.click(screen.getByRole('button', {name: /Type 1/}));

    expect(typeChangeSpy).toBeCalledWith('type1', 1);
});

test('Should call renderBlockContent with the correct arguments', () => {
    const renderBlockContentSpy = jest.fn();
    const value = {content: 'Test 1'};

    render(
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

    render(
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

    render(
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
