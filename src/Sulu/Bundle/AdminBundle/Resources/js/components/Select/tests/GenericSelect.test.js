/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, shallow} from 'enzyme';
import React from 'react';
import pretty from 'pretty';
import GenericSelect from '../GenericSelect';
import Divider from '../Divider';
import Option from '../Option';

jest.mock('../../../services/DOM/afterElementsRendered');

afterEach(() => document.body.innerHTML = '');

test('The component should render with the list closed', () => {
    const body = document.body;
    const optionIsSelected = () => false;
    const getLabelText = () => 'My Label text';
    const onSelect = () => {};
    const select = mount(
        <GenericSelect
            onSelect={onSelect}
            optionIsSelected={optionIsSelected}
            getLabelText={getLabelText} >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </GenericSelect>
    );
    expect(select.render()).toMatchSnapshot();
    expect(body.innerHTML).toBe('');
});

test('The component should render with an icon', () => {
    const body = document.body;
    const optionIsSelected = () => false;
    const getLabelText = () => 'My Label text';
    const onSelect = () => {};
    const select = mount(
        <GenericSelect
            icon="plus"
            onSelect={onSelect}
            optionIsSelected={optionIsSelected}
            getLabelText={getLabelText} >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </GenericSelect>
    );
    expect(select.render()).toMatchSnapshot();
    expect(body.innerHTML).toBe('');
});

test('The component should open the list when the label is clicked', () => {
    const body = document.body;
    const optionIsSelected = () => false;
    const getLabelText = () => 'My Label text';
    const onSelect = () => {};
    const select = mount(
        <GenericSelect
            onSelect={onSelect}
            optionIsSelected={optionIsSelected}
            getLabelText={getLabelText} >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </GenericSelect>
    );
    select.instance().handleLabelClick();
    expect(select.render()).toMatchSnapshot();
    expect(pretty(body.innerHTML)).toMatchSnapshot();
});

test('The component should trigger the select callback and close the list when an option is clicked', () => {
    const body = document.body;
    const onSelectSpy = jest.fn();
    const optionIsSelected = () => false;
    const getLabelText = () => 'My Label text';
    const select = mount(
        <GenericSelect
            onSelect={onSelectSpy}
            optionIsSelected={optionIsSelected}
            getLabelText={getLabelText} >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </GenericSelect>
    );
    select.instance().handleLabelClick();
    body.getElementsByTagName('button')[2].click();
    expect(onSelectSpy).toHaveBeenCalledWith('option-3');
    expect(body.innerHTML).toBe('');
});

test('The component should pass the centered child index to the overlay list', () => {
    const getLabelText = () => 'My Label text';
    const onSelect = () => {};
    const optionIsSelected = (child) => child.props.value === 'option-3';
    const select = shallow(
        <GenericSelect
            onSelect={onSelect}
            optionIsSelected={optionIsSelected}
            getLabelText={getLabelText} >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </GenericSelect>
    );
    const overlayList = select.find('OverlayList');
    expect(overlayList.props().centeredChildIndex).toBe(3);
});

test('The component should pass the selected property to the options', () => {
    const optionIsSelected = () => true;
    const getLabelText = () => 'My Label text';
    const onSelect = () => {};
    const select = shallow(
        <GenericSelect
            onSelect={onSelect}
            optionIsSelected={optionIsSelected}
            getLabelText={getLabelText} >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </GenericSelect>
    );
    const options = select.find('Option');
    expect(options.get(0).props.selected).toBe(true);
    expect(options.get(1).props.selected).toBe(true);
    expect(options.get(2).props.selected).toBe(true);
});
