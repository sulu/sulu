// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import {observable} from 'mobx';
import pretty from 'pretty';
import Assignment from '../Assignment';
import AssignmentStore from '../stores/AssignmentStore';

jest.mock('../../../utils', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../containers/Datagrid', () => function Datagrid() {
    return <div className="datagrid" />;
});

jest.mock('../../../containers/Datagrid/stores/DatagridStore', () => jest.fn(function(resourceKey) {
    this.clearSelection = jest.fn();
    this.destroy = jest.fn();
    this.resourceKey = resourceKey;
    this.select = jest.fn();
}));

jest.mock('../stores/AssignmentStore', () => jest.fn(function() {
    this.items = [];
    this.set = jest.fn();
    this.move = jest.fn();
    this.removeById = jest.fn();
}));

beforeEach(() => {
    const body = document.body;

    if (body) {
        body.innerHTML = '';
    }
});

test('Show with default plus icon', () => {
    expect(render(<Assignment onChange={jest.fn()} resourceKey="snippets" overlayTitle="Assignment" />))
        .toMatchSnapshot();
});

test('Show with passed label', () => {
    expect(render(
        <Assignment onChange={jest.fn()} label="Select Snippets" resourceKey="snippets" overlayTitle="Assignment" />
    )).toMatchSnapshot();
});

test('Show with passed icon', () => {
    expect(render(
        <Assignment onChange={jest.fn()} icon="su-document" resourceKey="snippets" overlayTitle="Assignment" />
    )).toMatchSnapshot();
});

test('Pass locale to DatagridOverlay', () => {
    const locale = observable.box('de');
    const assignment = mount(
        <Assignment onChange={jest.fn()} locale={locale} resourceKey="snippets" overlayTitle="Assignment" />
    );

    expect(assignment.find('DatagridOverlay').prop('locale').get()).toEqual('de');
});

test('Show with passed values as items in right locale', () => {
    const locale = observable.box('en');

    // $FlowFixMe
    AssignmentStore.mockImplementationOnce(function () {
        this.items = [{id: 1, title: 'Title 1'}, {id: 2, title: 'Title 2'}, {id: 5, title: 'Title 5'}];
    });

    expect(render(
        <Assignment
            displayProperties={['id', 'title']}
            onChange={jest.fn()}
            locale={locale}
            resourceKey="snippets"
            overlayTitle="Assignment"
            value={[1, 2, 5]}
        />
    )).toMatchSnapshot();

    expect(AssignmentStore).toBeCalledWith('snippets', [1, 2, 5], locale);
});

test('Should open an overlay', () => {
    const assignment = mount(<Assignment onChange={jest.fn()} resourceKey="snippets" overlayTitle="Assignment" />);

    assignment.find('Button[icon="su-plus"]').simulate('click');

    const body = document.body;
    expect(pretty(body ? body.innerHTML : null)).toMatchSnapshot();
});

test('Should close an overlay using the close button', () => {
    const assignment = mount(<Assignment onChange={jest.fn()} resourceKey="snippets" overlayTitle="Assignment" />);

    assignment.find('Button[icon="su-plus"]').simulate('click');

    const closeButton = document.querySelector('.su-x');
    if (closeButton) {
        closeButton.click();
    }

    assignment.update();
    expect(assignment.find('DatagridOverlay').prop('open')).toEqual(false);
});

test('Should close an overlay using the confirm button', () => {
    const assignment = mount(<Assignment onChange={jest.fn()} resourceKey="snippets" overlayTitle="Assignment" />);

    assignment.find('Button[icon="su-plus"]').simulate('click');

    const confirmButton = document.querySelector('button.primary');
    if (confirmButton) {
        confirmButton.click();
    }

    assignment.update();
    expect(assignment.find('DatagridOverlay').prop('open')).toEqual(false);
});

test('Should call the onChange callback when clicking the confirm button', () => {
    const changeSpy = jest.fn();
    const assignment = mount(<Assignment onChange={changeSpy} resourceKey="snippets" overlayTitle="Assignment" />);

    assignment.find('Button[icon="su-plus"]').simulate('click');
    const datagridStore = assignment.find('DatagridOverlay').instance().datagridStore;
    datagridStore.selections = [3, 7, 2];

    const confirmButton = document.querySelector('button.primary');
    if (confirmButton) {
        confirmButton.click();
    }

    expect(assignment.instance().assignmentStore.set).toBeCalledWith([3, 7, 2]);
});

test('Should instantiate the DatagridStore with the correct resourceKey and destroy it on unmount', () => {
    const assignment = mount(<Assignment onChange={jest.fn()} resourceKey="pages" overlayTitle="Assignment" />);

    assignment.find('Button[icon="su-plus"]').simulate('click');

    const datagridStore = assignment.find('DatagridOverlay').instance().datagridStore;
    expect(datagridStore.resourceKey).toEqual('pages');

    assignment.unmount();
    expect(datagridStore.destroy).toBeCalled();
});

test('Should instantiate the DatagridStore with the preselected ids', () => {
    // $FlowFixMe
    AssignmentStore.mockImplementationOnce(function () {
        this.items = [{id: 1}, {id: 5}, {id: 8}];
    });

    const assignment = mount(
        <Assignment onChange={jest.fn()} value={[1, 5, 8]} resourceKey="pages" overlayTitle="Assignment" />
    );

    assignment.find('Button[icon="su-plus"]').simulate('click');

    const datagridStore = assignment.find('DatagridOverlay').instance().datagridStore;
    expect(datagridStore.select).toBeCalledWith({id: 1});
    expect(datagridStore.select).toBeCalledWith({id: 5});
    expect(datagridStore.select).toBeCalledWith({id: 8});
});

test('Should reinstantiate the DatagridStore with the preselected ids when new props are received', () => {
    const locale = observable.box('en');

    // $FlowFixMe
    AssignmentStore.mockImplementationOnce(function () {
        this.items = [{id: 1}, {id: 5}, {id: 8}];
        this.loadItems = jest.fn();
    });

    const assignment = mount(
        <Assignment
            onChange={jest.fn()}
            locale={locale}
            value={[1, 5, 8]}
            resourceKey="pages"
            overlayTitle="Assignment"
        />
    );

    assignment.find('Button[icon="su-plus"]').simulate('click');

    const datagridStore = assignment.find('DatagridOverlay').instance().datagridStore;
    expect(datagridStore.select).toBeCalledWith({id: 1});
    expect(datagridStore.select).toBeCalledWith({id: 5});
    expect(datagridStore.select).toBeCalledWith({id: 8});

    assignment.setProps({value: [1, 3]});
    expect(datagridStore.clearSelection).toBeCalled();
    const loadItemsCall = assignment.instance().assignmentStore.loadItems.mock.calls[0];
    expect(loadItemsCall[0]).toEqual([1, 3]);
});

test('Should not reload items if all new ids have already been loaded', () => {
    const locale = observable.box('en');

    // $FlowFixMe
    AssignmentStore.mockImplementationOnce(function () {
        this.items = [{id: 1}, {id: 5}, {id: 8}];
        this.loadItems = jest.fn();
    });

    const assignment = mount(
        <Assignment
            onChange={jest.fn()}
            locale={locale}
            value={[1, 5, 8]}
            resourceKey="pages"
            overlayTitle="Assignment"
        />
    );

    assignment.find('Button[icon="su-plus"]').simulate('click');

    const datagridStore = assignment.find('DatagridOverlay').instance().datagridStore;
    expect(datagridStore.select).toBeCalledWith({id: 1});
    expect(datagridStore.select).toBeCalledWith({id: 5});
    expect(datagridStore.select).toBeCalledWith({id: 8});

    assignment.setProps({value: [1, 5]});
    expect(datagridStore.clearSelection).toBeCalled();
    expect(assignment.instance().assignmentStore.loadItems).not.toBeCalled();
});

test('Should not reinstantiate the DatagridStore with the preselected ids when new props have the same values', () => {
    const locale = observable.box('en');

    // $FlowFixMe
    AssignmentStore.mockImplementationOnce(function () {
        this.items = [{id: 1}, {id: 5}, {id: 8}];
        this.loadItems = jest.fn();
    });

    const assignment = mount(
        <Assignment
            onChange={jest.fn()}
            locale={locale}
            value={[1, 5, 8]}
            resourceKey="pages"
            overlayTitle="Assignment"
        />
    );

    assignment.find('Button[icon="su-plus"]').simulate('click');

    const datagridStore = assignment.find('DatagridOverlay').instance().datagridStore;

    assignment.setProps({value: [1, 5, 8]});
    expect(datagridStore.clearSelection).toBeCalled();
    expect(assignment.instance().assignmentStore.loadItems).not.toBeCalled();
});

test('Should remove an item when the remove button is clicked', () => {
    const changeSpy = jest.fn();
    const assignment = shallow(
        <Assignment onChange={changeSpy} resourceKey="snippets" value={[3, 7, 9]} overlayTitle="Assignment" />
    );

    assignment.find('MultiItemSelection').prop('onItemRemove')(7);
    expect(assignment.instance().assignmentStore.removeById).toBeCalledWith(7);
});

test('Should reorder the items on drag and drop', () => {
    const changeSpy = jest.fn();
    const assignment = shallow(
        <Assignment onChange={changeSpy} resourceKey="snippets" value={[3, 7, 9]} overlayTitle="Assignment" />
    );

    assignment.find('MultiItemSelection').prop('onItemsSorted')(1, 2);

    expect(assignment.instance().assignmentStore.move).toBeCalledWith(1, 2);
});
