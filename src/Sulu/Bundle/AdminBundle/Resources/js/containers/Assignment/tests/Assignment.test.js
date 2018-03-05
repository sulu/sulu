// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import pretty from 'pretty';
import Assignment from '../Assignment';

jest.mock('../../../utils', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../containers/Datagrid', () => function Datagrid() {
    return <div className="datagrid" />;
});

jest.mock('../../../containers/Datagrid/stores/DatagridStore', () => jest.fn(function(resourceKey) {
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
    expect(render(<Assignment onChange={jest.fn()} />)).toMatchSnapshot();
});

test('Show with passed icon', () => {
    expect(render(<Assignment onChange={jest.fn()} icon="su-document" />)).toMatchSnapshot();
});

test('Should open an overlay', () => {
    const assignment = mount(<Assignment onChange={jest.fn()} />);

    assignment.find('Button[icon="su-plus"]').simulate('click');

    const body = document.body;
    expect(pretty(body ? body.innerHTML : null)).toMatchSnapshot();
});

test('Should close an overlay using the close button', () => {
    const assignment = mount(<Assignment onChange={jest.fn()} />);

    assignment.find('Button[icon="su-plus"]').simulate('click');

    const closeButton = document.querySelector('.su-x');
    if (closeButton) {
        closeButton.click();
    }

    assignment.update();
    expect(assignment.find('DatagridOverlay').prop('open')).toEqual(false);
});

test('Should close an overlay using the confirm button', () => {
    const assignment = mount(<Assignment onChange={jest.fn()} />);

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
    const assignment = mount(<Assignment onChange={changeSpy} />);

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
    const assignment = mount(<Assignment onChange={jest.fn()} resourceKey="pages" />);

    assignment.find('Button[icon="su-plus"]').simulate('click');

    const datagridStore = assignment.find('DatagridOverlay').instance().datagridStore;
    expect(datagridStore.resourceKey).toEqual('pages');

    assignment.unmount();
    expect(datagridStore.destroy).toBeCalled();
});

test('Should instantiate the DatagridStore with the preselected ids', () => {
    const assignment = mount(<Assignment onChange={jest.fn()} preSelectedIds={[1, 5, 8]} resourceKey="pages" />);

    assignment.find('Button[icon="su-plus"]').simulate('click');

    const datagridStore = assignment.find('DatagridOverlay').instance().datagridStore;
    expect(datagridStore.select).toBeCalledWith(1);
    expect(datagridStore.select).toBeCalledWith(5);
});
