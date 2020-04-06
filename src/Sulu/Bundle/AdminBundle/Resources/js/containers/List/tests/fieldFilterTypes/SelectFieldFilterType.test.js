// @flow
import {mount, render} from 'enzyme';
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

    expect(changeSpy).toBeCalledWith(['test']);
});

test('Call onChange handler with undefined if the new selection is empty', () => {
    const changeSpy = jest.fn();
    const selectFieldFilterType = new SelectFieldFilterType(changeSpy, {options: {test: 'test'}}, undefined);
    const selectFieldFilterTypeForm = mount(selectFieldFilterType.getFormNode());

    selectFieldFilterTypeForm.find('CheckboxGroup').prop('onChange')([]);

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
