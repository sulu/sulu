// @flow
import React from 'react';
import {mount, shallow} from 'enzyme';
import SingleAutoComplete from '../SingleAutoComplete';

jest.mock('debounce', () => jest.fn((callback) => callback));

test('SingleAutoComplete should render with suggestions', () => {
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
        {id: 2, name: 'Suggestion 2'},
        {id: 3, name: 'Suggestion 3'},
    ];

    const singleAutoComplete = mount(
        <SingleAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            onSearch={jest.fn()}
            searchProperties={['name']}
            suggestions={suggestions}
            value={{name: 'Test'}}
        />
    );

    // suggestions are displayed when input field is focused
    singleAutoComplete.find('Input').prop('onFocus')();
    singleAutoComplete.update();

    expect(singleAutoComplete.render()).toMatchSnapshot();
    expect(singleAutoComplete.find('AutoCompletePopover').render()).toMatchSnapshot();
});

test('SingleAutoComplete should be disabled when in disabled state', () => {
    const suggestions = [
        {name: 'Suggestion 1'},
        {name: 'Suggestion 2'},
        {name: 'Suggestion 3'},
    ];

    const singleAutoComplete = mount(
        <SingleAutoComplete
            disabled={true}
            displayProperty="name"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            onSearch={jest.fn()}
            searchProperties={['name']}
            suggestions={suggestions}
            value={{name: 'Test'}}
        />
    );

    expect(singleAutoComplete.find('input').prop('disabled')).toEqual(true);
});

test('Clicking on a suggestion should call the onChange handler with the value of the selected Suggestion', () => {
    const changeSpy = jest.fn();

    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
        {id: 2, name: 'Suggestion 2'},
        {id: 3, name: 'Suggestion 3'},
    ];

    const singleAutoComplete = mount(
        <SingleAutoComplete
            displayProperty="name"
            onChange={changeSpy}
            onFinish={jest.fn()}
            onSearch={jest.fn()}
            searchProperties={['name']}
            suggestions={suggestions}
            value={{name: 'Test'}}
        />
    );

    // suggestions are displayed when input field is focused
    singleAutoComplete.find('Input').prop('onFocus')();
    singleAutoComplete.update();

    singleAutoComplete.find('Suggestion button').at(0).simulate('click');

    expect(changeSpy).toHaveBeenCalledWith(suggestions[0]);
});

test('Should call onChange with undefined if all characters are removed from input', () => {
    const changeSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
        {id: 2, name: 'Suggestion 2'},
        {id: 3, name: 'Suggestion 3'},
    ];

    const singleAutoComplete = shallow(
        <SingleAutoComplete
            displayProperty="name"
            onChange={changeSpy}
            onFinish={jest.fn()}
            onSearch={jest.fn()}
            searchProperties={['name']}
            suggestions={suggestions}
            value={{name: 'Test'}}
        />
    );

    expect(singleAutoComplete.find('Input').prop('value')).toEqual('Test');
    singleAutoComplete.find('Input').simulate('change', '');
    expect(changeSpy).toBeCalledWith(undefined);
});

test('Should call the onFinish callback when the Input lost focus', () => {
    const finishSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    const singleAutoComplete = shallow(
        <SingleAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            onFinish={finishSpy}
            onSearch={jest.fn()}
            searchProperties={['name']}
            suggestions={suggestions}
            value={{name: 'Test'}}
        />
    );

    singleAutoComplete.find('Input').simulate('blur');

    expect(finishSpy).toBeCalledWith();
});

test('Should fire onSearch callback and open popover when input field is focused', () => {
    const searchSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    const singleAutoComplete = shallow(
        <SingleAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            onSearch={searchSpy}
            searchProperties={['name']}
            suggestions={suggestions}
            value={{name: 'Test'}}
        />
    );

    expect(searchSpy).not.toBeCalled();
    expect(singleAutoComplete.find('AutoCompletePopover').prop('open')).toEqual(false);

    singleAutoComplete.find('Input').prop('onFocus')();
    expect(searchSpy).toBeCalledWith('Test');
    expect(singleAutoComplete.find('AutoCompletePopover').prop('open')).toEqual(true);
});

test('Should close popover when requested and reopen popover when input field is changed', () => {
    const searchSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    const singleAutoComplete = shallow(
        <SingleAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            onSearch={searchSpy}
            searchProperties={['name']}
            suggestions={suggestions}
            value={{name: 'Test'}}
        />
    );

    singleAutoComplete.find('Input').prop('onFocus')();
    expect(searchSpy).nthCalledWith(1, 'Test');
    expect(singleAutoComplete.find('AutoCompletePopover').prop('open')).toEqual(true);

    singleAutoComplete.find('AutoCompletePopover').prop('onClose')();
    expect(singleAutoComplete.find('AutoCompletePopover').prop('open')).toEqual(false);

    singleAutoComplete.find('Input').prop('onChange')('search term');
    expect(searchSpy).nthCalledWith(2, 'search term');
    expect(singleAutoComplete.find('AutoCompletePopover').prop('open')).toEqual(true);
});
