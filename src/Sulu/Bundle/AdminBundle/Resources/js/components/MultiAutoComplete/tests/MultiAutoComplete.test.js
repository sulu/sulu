// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import pretty from 'pretty';
import Mousetrap from 'mousetrap';
import MultiAutoComplete from '../MultiAutoComplete';

beforeEach(() => {
    Mousetrap.reset();
});

test('MultiAutoComplete should render', () => {
    const suggestions = [
        {name: 'Suggestion 1'},
        {name: 'Suggestion 2'},
        {name: 'Suggestion 3'},
    ];

    const value = [
        {id: 1, name: 'Test'},
        {id: 2, name: 'Test 2'},
    ];

    expect(render(
        <MultiAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            onSearch={jest.fn()}
            searchProperties={['name']}
            suggestions={suggestions}
            value={value}
        />
    )).toMatchSnapshot();
});

test('Render the MultiAutoComplete with open suggestions list', () => {
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
        {id: 2, name: 'Suggestion 2'},
        {id: 3, name: 'Suggestion 3'},
    ];

    const multiAutoComplete = mount(
        <MultiAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            onSearch={jest.fn()}
            searchProperties={['name']}
            suggestions={suggestions}
            value={[{id: 4, name: 'Test'}]}
        />
    );

    multiAutoComplete.instance().inputValue = 'test';
    multiAutoComplete.update();

    expect(multiAutoComplete.render()).toMatchSnapshot();
    expect(pretty(document.body ? document.body.innerHTML : '')).toMatchSnapshot();
});

test('Clicking on a suggestion should call the onChange handler with the value of the selected Suggestion', () => {
    const changeSpy = jest.fn();

    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
        {id: 2, name: 'Suggestion 2'},
        {id: 3, name: 'Suggestion 3'},
    ];

    const value = [
        {id: 5, name: 'Test'},
    ];

    const multiAutoComplete = mount(
        <MultiAutoComplete
            displayProperty="name"
            onChange={changeSpy}
            onFinish={jest.fn()}
            onSearch={jest.fn()}
            searchProperties={['name']}
            suggestions={suggestions}
            value={value}
        />
    );

    multiAutoComplete.instance().inputValue = 'test';
    multiAutoComplete.update();

    multiAutoComplete.find('Suggestion button').at(0).simulate('click');

    expect(changeSpy).toHaveBeenCalledWith([...value, suggestions[0]]);
});

test('Clicking on delete icon of a suggestion should call the onChange callback without the deleted Suggestion', () => {
    const changeSpy = jest.fn();

    const suggestions = [];

    const value = [
        {id: 5, name: 'Test'},
        {id: 6, name: 'Test'},
    ];

    const multiAutoComplete = mount(
        <MultiAutoComplete
            displayProperty="name"
            onChange={changeSpy}
            onFinish={jest.fn()}
            onSearch={jest.fn()}
            searchProperties={['name']}
            suggestions={suggestions}
            value={value}
        />
    );

    multiAutoComplete.find('Item').at(1).find('Icon').simulate('click');

    expect(changeSpy).toHaveBeenCalledWith([value[0]]);
});

test('Should call the onFinish callback when an item is added', () => {
    const finishSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    const multiAutoComplete = mount(
        <MultiAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            onFinish={finishSpy}
            onSearch={jest.fn()}
            searchProperties={['name']}
            suggestions={suggestions}
            value={[]}
        />
    );

    multiAutoComplete.instance().inputValue = 'test';
    multiAutoComplete.update();

    multiAutoComplete.find('Suggestion button').at(0).simulate('click');

    expect(finishSpy).toBeCalledWith();
});

test('Should not trigger any callbacks when input is not focused', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    const multiAutoComplete = mount(
        <MultiAutoComplete
            displayProperty="name"
            onChange={changeSpy}
            onFinish={finishSpy}
            onSearch={jest.fn()}
            searchProperties={['name']}
            suggestions={suggestions}
            value={[]}
        />
    );

    multiAutoComplete.instance().inputValue = 'test';
    multiAutoComplete.update();

    Mousetrap.trigger('enter');
    Mousetrap.trigger(',');

    expect(changeSpy).not.toBeCalled();
    expect(finishSpy).not.toBeCalled();
});

test('Should trigger callbacks when input matches a suggestion and input is focused', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    const multiAutoComplete = mount(
        <MultiAutoComplete
            displayProperty="name"
            onChange={changeSpy}
            onFinish={finishSpy}
            onSearch={jest.fn()}
            searchProperties={['name']}
            suggestions={suggestions}
            value={[]}
        />
    );

    multiAutoComplete.instance().inputValue = 'Suggestion 1';
    multiAutoComplete.update();
    multiAutoComplete.find('input').prop('onFocus')();

    Mousetrap.trigger('enter');
    Mousetrap.trigger(',');

    expect(changeSpy).toBeCalledWith([suggestions[0]]);
    expect(finishSpy).toBeCalledWith();
});

test('Should not trigger callbacks when input does not match a suggestion and input is focused', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    const multiAutoComplete = mount(
        <MultiAutoComplete
            displayProperty="name"
            onChange={changeSpy}
            onFinish={finishSpy}
            onSearch={jest.fn()}
            searchProperties={['name']}
            suggestions={suggestions}
            value={[]}
        />
    );

    multiAutoComplete.instance().inputValue = 'Suggestion';
    multiAutoComplete.update();
    multiAutoComplete.find('input').prop('onFocus')();

    Mousetrap.trigger('enter');
    Mousetrap.trigger(',');

    expect(changeSpy).not.toBeCalled();
    expect(finishSpy).not.toBeCalled();
});

test('Should not trigger callbacks when input matches a suggestion and input has lost focus', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    const multiAutoComplete = mount(
        <MultiAutoComplete
            displayProperty="name"
            onChange={changeSpy}
            onFinish={finishSpy}
            onSearch={jest.fn()}
            searchProperties={['name']}
            suggestions={suggestions}
            value={[]}
        />
    );

    multiAutoComplete.instance().inputValue = 'Suggestion 1';
    multiAutoComplete.update();
    multiAutoComplete.find('input').prop('onFocus')();
    multiAutoComplete.find('input').prop('onBlur')();

    Mousetrap.trigger('enter');
    Mousetrap.trigger(',');

    expect(changeSpy).not.toBeCalled();
    expect(finishSpy).not.toBeCalled();
});

test('Should trigger callbacks when input does not match a suggestion and allowAdd is set', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    const multiAutoComplete = mount(
        <MultiAutoComplete
            allowAdd={true}
            displayProperty="name"
            idProperty="name"
            onChange={changeSpy}
            onFinish={finishSpy}
            onSearch={jest.fn()}
            searchProperties={['name']}
            suggestions={suggestions}
            value={[]}
        />
    );

    multiAutoComplete.instance().inputValue = 'Suggestion';
    multiAutoComplete.update();
    multiAutoComplete.find('input').prop('onFocus')();

    Mousetrap.trigger('enter');
    Mousetrap.trigger(',');

    expect(changeSpy).toBeCalledWith([{name: 'Suggestion'}]);
    expect(finishSpy).toBeCalledWith();
});
