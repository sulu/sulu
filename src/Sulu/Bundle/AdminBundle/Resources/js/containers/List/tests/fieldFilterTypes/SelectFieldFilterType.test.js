// @flow
import {mount, render} from 'enzyme';
import {observable} from 'mobx';
import SelectFieldFilterType from '../../fieldFilterTypes/SelectFieldFilterType';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

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
    expect(render(selectFieldFilterType.getFormNode())).toMatchSnapshot();
});

test('Render with value set by setValue', () => {
    const selectFieldFilterType = new SelectFieldFilterType(
        jest.fn(),
        {options: {audio: 'sulu_media.audio'}},
        undefined
    );

    selectFieldFilterType.setValue(['audio']);
    expect(render(selectFieldFilterType.getFormNode())).toMatchSnapshot();
});

test('Pass correct props to CheckboxGroup', () => {
    const selectFieldFilterType = new SelectFieldFilterType(
        jest.fn(),
        {options: {audio: 'Audio', image: 'Image', video: 'Video'}},
        ['audio', 'video']
    );

    const selectFieldFilterTypeForm = mount(selectFieldFilterType.getFormNode());

    expect(selectFieldFilterTypeForm.find('CheckboxGroup').prop('values')).toEqual(['audio', 'video']);

    expect(selectFieldFilterTypeForm.find('Checkbox')).toHaveLength(3);
    expect(selectFieldFilterTypeForm.find('Checkbox').at(0).prop('value')).toEqual('audio');
    expect(selectFieldFilterTypeForm.find('Checkbox').at(0).text()).toEqual('Audio');
    expect(selectFieldFilterTypeForm.find('Checkbox').at(1).prop('value')).toEqual('image');
    expect(selectFieldFilterTypeForm.find('Checkbox').at(1).text()).toEqual('Image');
    expect(selectFieldFilterTypeForm.find('Checkbox').at(2).prop('value')).toEqual('video');
    expect(selectFieldFilterTypeForm.find('Checkbox').at(2).text()).toEqual('Video');
});

test('Call onChange handler with new value', () => {
    const changeSpy = jest.fn();
    const selectFieldFilterType = new SelectFieldFilterType(changeSpy, {options: {test: 'test'}}, undefined);
    const selectFieldFilterTypeForm = mount(selectFieldFilterType.getFormNode());

    selectFieldFilterTypeForm.find('CheckboxGroup').prop('onChange')(['test']);

    expect(changeSpy).toHaveBeenCalledWith(['test']);
});

test('Call onChange handler with undefined if the new selection is empty', () => {
    const changeSpy = jest.fn();
    const selectFieldFilterType = new SelectFieldFilterType(changeSpy, {options: {test: 'test'}}, undefined);
    const selectFieldFilterTypeForm = mount(selectFieldFilterType.getFormNode());

    selectFieldFilterTypeForm.find('CheckboxGroup').prop('onChange')([]);

    expect(changeSpy).toHaveBeenCalledWith(undefined);
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

    const selectFieldFilterTypeForm = mount(selectFieldFilterType.getFormNode());

    expect(selectFieldFilterTypeForm.find('Checkbox')).toHaveLength(3);
    expect(selectFieldFilterTypeForm.find('Checkbox').at(0).prop('value')).toEqual('0');
    expect(selectFieldFilterTypeForm.find('Checkbox').at(0).text()).toEqual('app.job.jobSource.0');
    expect(selectFieldFilterTypeForm.find('Checkbox').at(1).prop('value')).toEqual('1');
    expect(selectFieldFilterTypeForm.find('Checkbox').at(1).text()).toEqual('app.job.jobSource.1');
    expect(selectFieldFilterTypeForm.find('Checkbox').at(2).prop('value')).toEqual('2');
    expect(selectFieldFilterTypeForm.find('Checkbox').at(2).text()).toEqual('app.job.jobSource.2');
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

test('Pass correct props to CheckboxGroup if the values are numbers', () => {
    const selectFieldFilterType = new SelectFieldFilterType(
        jest.fn(),
        {options: {'1': 'Open', '2': 'Approved', '3': 'Rejected'}},
        // $FlowFixMe: numeric option keys are restored as numbers from the URL
        [1, 3]
    );

    const selectFieldFilterTypeForm = mount(selectFieldFilterType.getFormNode());

    expect(selectFieldFilterTypeForm.find('CheckboxGroup').prop('values')).toEqual(['1', '3']);

    expect(selectFieldFilterTypeForm.find('Checkbox').at(0).prop('checked')).toEqual(true);
    expect(selectFieldFilterTypeForm.find('Checkbox').at(1).prop('checked')).toEqual(false);
    expect(selectFieldFilterTypeForm.find('Checkbox').at(2).prop('checked')).toEqual(true);
});

test('Call onChange handler without duplicates if the values are numbers', () => {
    const changeSpy = jest.fn();
    const selectFieldFilterType = new SelectFieldFilterType(
        changeSpy,
        {options: {'1': 'Open', '2': 'Approved', '3': 'Rejected'}},
        // $FlowFixMe: numeric option keys are restored as numbers from the URL
        [1, 3]
    );

    const selectFieldFilterTypeForm = mount(selectFieldFilterType.getFormNode());

    selectFieldFilterTypeForm.find('Checkbox').at(1).prop('onChange')(true, '2');
    expect(changeSpy).toHaveBeenLastCalledWith(['1', '3', '2']);

    selectFieldFilterTypeForm.find('Checkbox').at(0).prop('onChange')(false, '1');
    expect(changeSpy).toHaveBeenLastCalledWith(['3']);
});

test('Return value node with number values', () => {
    const selectFieldFilterType = new SelectFieldFilterType(
        jest.fn(),
        {options: {'1': 'Open', '2': 'Approved', '3': 'Rejected'}},
        undefined
    );

    // $FlowFixMe: numeric option keys are restored as numbers from the URL
    const valueNodePromise = selectFieldFilterType.getValueNode([1, 3]);

    if (!valueNodePromise) {
        throw new Error('The getValueNode function must return a promise!');
    }

    return valueNodePromise.then((valueNode) => {
        expect(valueNode).toEqual('Open, Rejected');
    });
});

test('Pass correct props to CheckboxGroup if the values are an observable array of numbers', () => {
    const selectFieldFilterType = new SelectFieldFilterType(
        jest.fn(),
        {options: {'1': 'Open', '2': 'Approved', '3': 'Rejected'}},
        // $FlowFixMe: numeric option keys are restored as numbers from the URL
        observable([1, 3])
    );

    const selectFieldFilterTypeForm = mount(selectFieldFilterType.getFormNode());

    expect(selectFieldFilterTypeForm.find('CheckboxGroup').prop('values')).toEqual(['1', '3']);
});
