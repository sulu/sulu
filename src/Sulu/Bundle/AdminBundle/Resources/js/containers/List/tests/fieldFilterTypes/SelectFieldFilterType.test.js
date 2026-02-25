// @flow
import React from 'react';
import {observable} from 'mobx';
import SelectFieldFilterType from '../../fieldFilterTypes/SelectFieldFilterType';
import Checkbox, {CheckboxGroup} from '../../../../components/Checkbox';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

const getCheckboxNodeProps = (selectFieldFilterType) => {
    const checkboxGroupNode = selectFieldFilterType.getFormNode();

    if (checkboxGroupNode.type !== CheckboxGroup) {
        throw new Error('CheckboxGroup node was not found');
    }

    const checkboxNodes = React.Children.toArray(checkboxGroupNode.props.children);

    checkboxNodes.forEach((checkboxNode) => {
        if (checkboxNode.type !== Checkbox) {
            throw new Error('Checkbox node was not found');
        }
    });

    return {
        checkboxGroupNode,
        checkboxNodes,
    };
};

test.each([
    [undefined, 'parameters'],
    [4, 'object'],
])('Throw error if "%s" is passed as a parameter', (parameters, errorMessage) => {
    const selectFieldFilterType = new SelectFieldFilterType(jest.fn(), parameters, undefined);
    expect(() => selectFieldFilterType.getFormNode()).toThrow(errorMessage);
});

test.each([
    [['audio', 'video'], {options: {audio: 'sulu_media.audio', video: 'sulu_media.video'}}],
    [undefined, {options: {image: 'sulu_media.image'}}],
    [['image', 'video'], {options: {image: 'sulu_media.image', video: 'sulu_media.video'}}],
])('Render with a value of "%s"', (value, parameters) => {
    const selectFieldFilterType = new SelectFieldFilterType(jest.fn(), parameters, value);
    const {checkboxGroupNode, checkboxNodes} = getCheckboxNodeProps(selectFieldFilterType);

    expect(checkboxGroupNode.props.values).toEqual(value || []);
    expect(checkboxNodes.map((checkboxNode) => checkboxNode.props.value)).toEqual(Object.keys(parameters.options));
    expect(checkboxNodes.map((checkboxNode) => checkboxNode.props.children)).toEqual(Object.values(parameters.options));
});

test('Render with value set by setValue', () => {
    const selectFieldFilterType = new SelectFieldFilterType(
        jest.fn(),
        {options: {audio: 'sulu_media.audio'}},
        undefined
    );

    selectFieldFilterType.setValue(['audio']);
    const {checkboxGroupNode, checkboxNodes} = getCheckboxNodeProps(selectFieldFilterType);

    expect(checkboxGroupNode.props.values).toEqual(['audio']);
    expect(checkboxNodes).toHaveLength(1);
    expect(checkboxNodes[0].props.value).toEqual('audio');
    expect(checkboxNodes[0].props.children).toEqual('sulu_media.audio');
});

test('Pass correct props to CheckboxGroup', () => {
    const selectFieldFilterType = new SelectFieldFilterType(
        jest.fn(),
        {options: {audio: 'Audio', image: 'Image', video: 'Video'}},
        ['audio', 'video']
    );

    const {checkboxGroupNode, checkboxNodes} = getCheckboxNodeProps(selectFieldFilterType);

    expect(checkboxGroupNode.props.values).toEqual(['audio', 'video']);
    expect(checkboxNodes).toHaveLength(3);
    expect(checkboxNodes[0].props.value).toEqual('audio');
    expect(checkboxNodes[0].props.children).toEqual('Audio');
    expect(checkboxNodes[1].props.value).toEqual('image');
    expect(checkboxNodes[1].props.children).toEqual('Image');
    expect(checkboxNodes[2].props.value).toEqual('video');
    expect(checkboxNodes[2].props.children).toEqual('Video');
});

test('Call onChange handler with new value', () => {
    const changeSpy = jest.fn();
    const selectFieldFilterType = new SelectFieldFilterType(changeSpy, {options: {test: 'test'}}, undefined);
    const {checkboxGroupNode} = getCheckboxNodeProps(selectFieldFilterType);

    checkboxGroupNode.props.onChange(['test']);

    expect(changeSpy).toBeCalledWith(['test']);
});

test('Call onChange handler with undefined if the new selection is empty', () => {
    const changeSpy = jest.fn();
    const selectFieldFilterType = new SelectFieldFilterType(changeSpy, {options: {test: 'test'}}, undefined);
    const {checkboxGroupNode} = getCheckboxNodeProps(selectFieldFilterType);

    checkboxGroupNode.props.onChange([]);

    expect(changeSpy).toBeCalledWith(undefined);
});

test.each([
    [['audio', 'video'], 'Audio, Video'],
    [['image'], 'Image'],
    [undefined, null],
])('Return value node with value "%s"', (value, expectedValueNode) => {
    const selectFieldFilterType = new SelectFieldFilterType(
        jest.fn(),
        {options: {audio: 'Audio', image: 'Image', video: 'Video'}},
        undefined
    );

    const valueNodePromise = selectFieldFilterType.getValueNode(value);

    if (!valueNodePromise) {
        throw new Error('The getValueNode function must return a promise!');
    }

    return valueNodePromise.then((valueNode) => {
        expect(valueNode).toEqual(expectedValueNode);
    });
});

test('Handle observable array options with numeric keys', () => {
    const observableOptions = observable(['app.job.jobSource.0', 'app.job.jobSource.1', 'app.job.jobSource.2']);
    const selectFieldFilterType = new SelectFieldFilterType(
        jest.fn(),
        {options: observableOptions},
        undefined
    );

    const {checkboxNodes} = getCheckboxNodeProps(selectFieldFilterType);

    expect(checkboxNodes).toHaveLength(3);
    expect(checkboxNodes[0].props.value).toEqual('0');
    expect(checkboxNodes[0].props.children).toEqual('app.job.jobSource.0');
    expect(checkboxNodes[1].props.value).toEqual('1');
    expect(checkboxNodes[1].props.children).toEqual('app.job.jobSource.1');
    expect(checkboxNodes[2].props.value).toEqual('2');
    expect(checkboxNodes[2].props.children).toEqual('app.job.jobSource.2');
});

test('Return value node observable array options', () => {
    const observableOptions = observable(['Option Zero', 'Option One', 'Option Two']);
    const selectFieldFilterType = new SelectFieldFilterType(
        jest.fn(),
        {options: observableOptions},
        undefined
    );

    const valueNodePromise = selectFieldFilterType.getValueNode(['0', '2']);

    if (!valueNodePromise) {
        throw new Error('The getValueNode function must return a promise!');
    }

    return valueNodePromise.then((valueNode) => {
        expect(valueNode).toEqual('Option Zero, Option Two');
    });
});
