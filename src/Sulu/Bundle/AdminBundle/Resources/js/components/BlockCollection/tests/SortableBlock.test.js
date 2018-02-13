// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import SortableBlock from '../SortableBlock';

jest.mock('react-sortable-hoc', () => ({
    SortableElement: jest.fn().mockImplementation((component) => component),
    SortableHandle: jest.fn().mockImplementation((component) => component),
}));

test('Render collapsed sortable block', () => {
    expect(render(
        <SortableBlock
            expanded={false}
            onCollapse={jest.fn()}
            onExpand={jest.fn()}
            onRemove={jest.fn()}
            renderBlockContent={jest.fn()}
            sortIndex={1}
            value={{content: 'Test Content'}}
        />
    )).toMatchSnapshot();
});

test('Render expanded sortable block', () => {
    const renderBlockContent = jest.fn().mockImplementation((value) => 'Test for ' + value.content);

    expect(render(
        <SortableBlock
            expanded={true}
            onCollapse={jest.fn()}
            onExpand={jest.fn()}
            onRemove={jest.fn()}
            renderBlockContent={renderBlockContent}
            sortIndex={1}
            value={{content: 'Test Content'}}
        />
    )).toMatchSnapshot();
});

test('Render expanded sortable block with types', () => {
    const renderBlockContent = jest.fn().mockImplementation(
        (value, type) => 'Test for ' + value.content + (type ? ' and type ' + type : '')
    );

    expect(render(
        <SortableBlock
            activeType={'type2'}
            expanded={true}
            onCollapse={jest.fn()}
            onExpand={jest.fn()}
            onRemove={jest.fn()}
            renderBlockContent={renderBlockContent}
            sortIndex={1}
            types={{type1: 'Type 1', type2: 'Type 2'}}
            value={{content: 'Test Content'}}
        />
    )).toMatchSnapshot();
});

test('Should call onCollapse when the block is being collapsed', () => {
    const collapseSpy = jest.fn();
    const expandSpy = jest.fn();
    const removeSpy = jest.fn();

    const sortableBlock = shallow(
        <SortableBlock
            expanded={true}
            onCollapse={collapseSpy}
            onExpand={expandSpy}
            onRemove={removeSpy}
            renderBlockContent={jest.fn()}
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
            expanded={true}
            onCollapse={collapseSpy}
            onExpand={expandSpy}
            onRemove={removeSpy}
            renderBlockContent={jest.fn()}
            sortIndex={1}
            value={{content: 'Test Content'}}
        />
    );

    sortableBlock.find('Block').prop('onExpand')();

    expect(collapseSpy).not.toBeCalled();
    expect(expandSpy).toBeCalledWith(1);
    expect(removeSpy).not.toBeCalled();
});

test('Should call onRemove when the block is being removed', () => {
    const collapseSpy = jest.fn();
    const expandSpy = jest.fn();
    const removeSpy = jest.fn();

    const sortableBlock = shallow(
        <SortableBlock
            expanded={true}
            onCollapse={collapseSpy}
            onExpand={expandSpy}
            onRemove={removeSpy}
            renderBlockContent={jest.fn()}
            sortIndex={1}
            value={{content: 'Test Content'}}
        />
    );

    sortableBlock.find('Block').prop('onRemove')();

    expect(collapseSpy).not.toBeCalled();
    expect(expandSpy).not.toBeCalled();
    expect(removeSpy).toBeCalledWith(1);
});

test('Should call onTypeChange when the block has changed its type', () => {
    const typeChangeSpy = jest.fn();

    const sortableBlock = shallow(
        <SortableBlock
            expanded={true}
            onCollapse={jest.fn()}
            onExpand={jest.fn()}
            onRemove={jest.fn()}
            onTypeChange={typeChangeSpy}
            renderBlockContent={jest.fn()}
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
            expanded={true}
            onCollapse={jest.fn()}
            onExpand={jest.fn()}
            onRemove={jest.fn()}
            renderBlockContent={renderBlockContentSpy}
            sortIndex={7}
            value={value}
        />
    );

    expect(renderBlockContentSpy).toBeCalledWith(value, undefined, 7);
});

test('Should call renderBlockContent with the correct arguments when types are involved', () => {
    const renderBlockContentSpy = jest.fn();
    const value = {content: 'Test 2'};

    shallow(
        <SortableBlock
            activeType="test"
            expanded={true}
            onCollapse={jest.fn()}
            onExpand={jest.fn()}
            onRemove={jest.fn()}
            renderBlockContent={renderBlockContentSpy}
            sortIndex={7}
            value={value}
        />
    );

    expect(renderBlockContentSpy).toBeCalledWith(value, 'test', 7);
});
