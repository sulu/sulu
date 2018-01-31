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

    expect(collapseSpy).toBeCalled();
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
    expect(expandSpy).toBeCalled();
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
    expect(removeSpy).toBeCalled();
});
