// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import AutoCompletePopover from '../AutoCompletePopover';
import Popover from '../../Popover';

jest.mock('../../Popover', () => ({children}) => children(jest.fn(), {}));

test('Popover should be hidden when open is set to false', () => {
    const autoCompletePopover = shallow(
        <AutoCompletePopover
            anchorElement={jest.fn()}
            onSelect={jest.fn()}
            open={false}
            query=""
            searchProperties={[]}
            suggestions={[]}
        />
    );

    expect(autoCompletePopover.find(Popover).prop('open')).toEqual(false);
});

test('Popover should be shown when open is set to true', () => {
    const autoCompletePopover = shallow(
        <AutoCompletePopover
            anchorElement={jest.fn()}
            onSelect={jest.fn()}
            open={true}
            query=""
            searchProperties={[]}
            suggestions={[]}
        />
    );

    expect(autoCompletePopover.find(Popover).prop('open')).toEqual(true);
});

test('Render with highlighted suggestions', () => {
    const suggestions = [
        {id: 1, name: 'Test 1'},
        {id: 2, name: 'Test 2'},
    ];

    expect(render(
        <AutoCompletePopover
            anchorElement={jest.fn()}
            onSelect={jest.fn()}
            open={true}
            query="Test"
            searchProperties={['name']}
            suggestions={suggestions}
        />
    )).toMatchSnapshot();
});

test('Call onSelect with first suggestion on close', () => {
    const suggestions = [
        {id: 1, name: 'Test 1'},
        {id: 2, name: 'Test 2'},
    ];

    const selectSpy = jest.fn();
    const autoCompletePopover = shallow(
        <AutoCompletePopover
            anchorElement={jest.fn()}
            onSelect={selectSpy}
            open={true}
            query="Test"
            searchProperties={['name']}
            suggestions={suggestions}
        />
    );

    autoCompletePopover.find(Popover).prop('onClose')();
    expect(selectSpy).toBeCalledWith(suggestions[0]);
});

test('Call onSelect with clicked suggestion', () => {
    const suggestions = [
        {id: 1, name: 'Test 1'},
        {id: 2, name: 'Test 2'},
    ];

    const selectSpy = jest.fn();
    const autoCompletePopover = mount(
        <AutoCompletePopover
            anchorElement={jest.fn()}
            onSelect={selectSpy}
            open={true}
            query="Test"
            searchProperties={['name']}
            suggestions={suggestions}
        />
    );

    expect(autoCompletePopover.find('Suggestion').at(1).prop('value')).toBe(suggestions[1]);
    autoCompletePopover.find('Suggestion').at(1).prop('onSelect')(suggestions[1]);
    expect(selectSpy).toBeCalledWith(suggestions[1]);
});
