// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import pretty from 'pretty';
import Assignment from '../Assignment';

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

beforeEach(() => {
    const body = document.body;

    if (body) {
        body.innerHTML = '';
    }
});

test('Show with default plus icon', () => {
    expect(render(<Assignment onChange={jest.fn()} resourceKey="snippets" title="Assignment" />)).toMatchSnapshot();
});

test('Show with passed icon', () => {
    expect(render(
        <Assignment onChange={jest.fn()} icon="su-document" resourceKey="snippets" title="Assignment" />
    )).toMatchSnapshot();
});

test('Show with passed values as items', () => {
    expect(render(
        <Assignment onChange={jest.fn()} value={[1, 2, 5]} resourceKey="snippets" title="Assignment" />
    )).toMatchSnapshot();
});

test('Should open an overlay', () => {
    const assignment = mount(<Assignment onChange={jest.fn()} resourceKey="snippets" title="Assignment" />);

    assignment.find('Button[icon="su-plus"]').simulate('click');

    const body = document.body;
    expect(pretty(body ? body.innerHTML : null)).toMatchSnapshot();
});

test('Should close an overlay using the close button', () => {
    const assignment = mount(<Assignment onChange={jest.fn()} resourceKey="snippets" title="Assignment" />);

    assignment.find('Button[icon="su-plus"]').simulate('click');

    const closeButton = document.querySelector('.su-x');
    if (closeButton) {
        closeButton.click();
    }

    assignment.update();
    expect(assignment.find('DatagridOverlay').prop('open')).toEqual(false);
});

test('Should close an overlay using the confirm button', () => {
    const assignment = mount(<Assignment onChange={jest.fn()} resourceKey="snippets" title="Assignment" />);

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
    const assignment = mount(<Assignment onChange={changeSpy} resourceKey="snippets" title="Assignment" />);

    assignment.find('Button[icon="su-plus"]').simulate('click');
    const datagridStore = assignment.find('DatagridOverlay').instance().datagridStore;
    datagridStore.selections = [3, 7, 2];

    const confirmButton = document.querySelector('button.primary');
    if (confirmButton) {
        confirmButton.click();
    }

    expect(changeSpy).toBeCalledWith([3, 7, 2]);
});

test('Should instantiate the DatagridStore with the correct resourceKey and destroy it on unmount', () => {
    const assignment = mount(<Assignment onChange={jest.fn()} resourceKey="pages" title="Assignment" />);

    assignment.find('Button[icon="su-plus"]').simulate('click');

    const datagridStore = assignment.find('DatagridOverlay').instance().datagridStore;
    expect(datagridStore.resourceKey).toEqual('pages');

    assignment.unmount();
    expect(datagridStore.destroy).toBeCalled();
});

test('Should instantiate the DatagridStore with the preselected ids', () => {
    const assignment = mount(
        <Assignment onChange={jest.fn()} value={[1, 5, 8]} resourceKey="pages" title="Assignment" />
    );

    assignment.find('Button[icon="su-plus"]').simulate('click');

    const datagridStore = assignment.find('DatagridOverlay').instance().datagridStore;
    expect(datagridStore.select).toBeCalledWith(1);
    expect(datagridStore.select).toBeCalledWith(5);
});

test('Should reinstantiate the DatagridStore with the preselected ids when new props are received', () => {
    const assignment = mount(
        <Assignment onChange={jest.fn()} value={[1, 5, 8]} resourceKey="pages" title="Assignment" />
    );

    assignment.find('Button[icon="su-plus"]').simulate('click');

    const datagridStore = assignment.find('DatagridOverlay').instance().datagridStore;
    expect(datagridStore.select).toBeCalledWith(1);
    expect(datagridStore.select).toBeCalledWith(5);

    assignment.setProps({value: [1, 3]});
    expect(datagridStore.clearSelection).toBeCalled();
    expect(datagridStore.select).toBeCalledWith(1);
    expect(datagridStore.select).toBeCalledWith(3);
});

test('Should remove an item when the remove button is clicked', () => {
    const changeSpy = jest.fn();
    const assignment = shallow(
        <Assignment onChange={changeSpy} resourceKey="snippets" value={[3, 7, 9]} title="Assignment" />
    );

    assignment.find('MultiItemSelection').prop('onItemRemove')(7);
    expect(changeSpy).toBeCalledWith([3, 9]);
});

test('Should reorder the items on drag and drop', () => {
    const changeSpy = jest.fn();
    const assignment = shallow(
        <Assignment onChange={changeSpy} resourceKey="snippets" value={[3, 7, 9]} title="Assignment" />
    );

    assignment.find('MultiItemSelection').prop('onItemsSorted')(1, 2);

    expect(changeSpy).toBeCalledWith([3, 9, 7]);
});
