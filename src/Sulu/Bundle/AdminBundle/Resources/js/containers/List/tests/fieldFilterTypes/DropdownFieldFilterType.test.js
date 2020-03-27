// @flow
import {mount, render} from 'enzyme';
import DropdownFieldFilterType from '../../fieldFilterTypes/DropdownFieldFilterType';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test.each([
    [undefined, 'parameters'],
    [4, 'object'],
])('Throw error if "%s" is passed as a parameter', (parameters, errorMessage) => {
    const dropdownFieldFilterType = new DropdownFieldFilterType(jest.fn(), parameters, undefined);
    expect(() => dropdownFieldFilterType.getFormNode()).toThrow(errorMessage);
});

test.each([
    [['audio', 'video'], {options: {audio: 'sulu_media.audio', video: 'sulu_media.video'}}],
    [undefined, {options: {image: 'sulu_media.image'}}],
    [['image', 'video'], {options: {image: 'sulu_media.image', video: 'sulu_media.video'}}],
])('Render with a value of "%s"', (value, parameters) => {
    const dropdownFieldFilterType = new DropdownFieldFilterType(jest.fn(), parameters, value);
    expect(render(dropdownFieldFilterType.getFormNode())).toMatchSnapshot();
});

test('Render with value set by setValue', () => {
    const dropdownFieldFilterType = new DropdownFieldFilterType(
        jest.fn(),
        {options: {audio: 'sulu_media.audio'}},
        undefined
    );

    dropdownFieldFilterType.setValue(['audio']);
    expect(render(dropdownFieldFilterType.getFormNode())).toMatchSnapshot();
});

test('Pass correct props to MultiSelect', () => {
    const dropdownFieldFilterType = new DropdownFieldFilterType(
        jest.fn(),
        {options: {audio: 'Audio', image: 'Image', video: 'Video'}},
        ['audio', 'video']
    );

    const dropdownFieldFilterTypeForm = mount(dropdownFieldFilterType.getFormNode());

    expect(dropdownFieldFilterTypeForm.find('MultiSelect').prop('values')).toEqual(['audio', 'video']);

    dropdownFieldFilterTypeForm.find('DisplayValue').simulate('click');
    expect(dropdownFieldFilterTypeForm.find('MultiSelect Option')).toHaveLength(3);
    expect(dropdownFieldFilterTypeForm.find('MultiSelect Option').at(0).prop('value')).toEqual('audio');
    expect(dropdownFieldFilterTypeForm.find('MultiSelect Option').at(0).text()).toEqual('Audio');
    expect(dropdownFieldFilterTypeForm.find('MultiSelect Option').at(1).prop('value')).toEqual('image');
    expect(dropdownFieldFilterTypeForm.find('MultiSelect Option').at(1).text()).toEqual('Image');
    expect(dropdownFieldFilterTypeForm.find('MultiSelect Option').at(2).prop('value')).toEqual('video');
    expect(dropdownFieldFilterTypeForm.find('MultiSelect Option').at(2).text()).toEqual('Video');
});

test('Call onChange handler with new value', () => {
    const changeSpy = jest.fn();
    const dropdownFieldFilterType = new DropdownFieldFilterType(changeSpy, {options: {test: 'test'}}, undefined);
    const dropdownFieldFilterTypeForm = mount(dropdownFieldFilterType.getFormNode());

    dropdownFieldFilterTypeForm.find('MultiSelect').prop('onChange')(['test']);

    expect(changeSpy).toBeCalledWith(['test']);
});

test('Call onChange handler with undefined if the new selection is empty', () => {
    const changeSpy = jest.fn();
    const dropdownFieldFilterType = new DropdownFieldFilterType(changeSpy, {options: {test: 'test'}}, undefined);
    const dropdownFieldFilterTypeForm = mount(dropdownFieldFilterType.getFormNode());

    dropdownFieldFilterTypeForm.find('MultiSelect').prop('onChange')([]);

    expect(changeSpy).toBeCalledWith(undefined);
});

test.each([
    [['audio', 'video'], 'Audio, Video'],
    [['image'], 'Image'],
    [undefined, null],
])('Return value node with value "%s"', (value, expectedValueNode) => {
    const dropdownFieldFilterType = new DropdownFieldFilterType(
        jest.fn(),
        {options: {audio: 'Audio', image: 'Image', video: 'Video'}},
        undefined
    );

    const valueNodePromise = dropdownFieldFilterType.getValueNode(value);

    if (!valueNodePromise) {
        throw new Error('The getValueNode function must return a promise!');
    }

    return valueNodePromise.then((valueNode) => {
        expect(valueNode).toEqual(expectedValueNode);
    });
});
