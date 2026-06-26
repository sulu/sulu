// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import SelectFieldFilterType from '../../fieldFilterTypes/SelectFieldFilterType';

jest.mock('../../../../utils/Translator');

function expectCheckboxValue(label: string, value: string) {
    expect((screen.getByLabelText(label): any).value).toBe(value);
}

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
    const {asFragment} = render(selectFieldFilterType.getFormNode());

    expect(asFragment()).toMatchSnapshot();
});

test('Render with value set by setValue', () => {
    const selectFieldFilterType = new SelectFieldFilterType(
        jest.fn(),
        {options: {audio: 'sulu_media.audio'}},
        undefined
    );

    selectFieldFilterType.setValue(['audio']);
    const {asFragment} = render(selectFieldFilterType.getFormNode());

    expect(asFragment()).toMatchSnapshot();
});

test('Pass correct props to CheckboxGroup', () => {
    const selectFieldFilterType = new SelectFieldFilterType(
        jest.fn(),
        {options: {audio: 'Audio', image: 'Image', video: 'Video'}},
        ['audio', 'video']
    );

    render(selectFieldFilterType.getFormNode());

    const checkboxes = screen.getAllByRole('checkbox');

    expect(checkboxes).toHaveLength(3);
    expectCheckboxValue('Audio', 'audio');
    expect(screen.getByLabelText('Audio')).toBeChecked();
    expectCheckboxValue('Image', 'image');
    expect(screen.getByLabelText('Image')).not.toBeChecked();
    expectCheckboxValue('Video', 'video');
    expect(screen.getByLabelText('Video')).toBeChecked();
});

test('Call onChange handler with new value', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const selectFieldFilterType = new SelectFieldFilterType(changeSpy, {options: {test: 'test'}}, undefined);

    render(selectFieldFilterType.getFormNode());

    await user.click(screen.getByLabelText('test'));

    expect(changeSpy).toHaveBeenCalledWith(['test']);
});

test('Call onChange handler with undefined if the new selection is empty', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const selectFieldFilterType = new SelectFieldFilterType(changeSpy, {options: {test: 'test'}}, ['test']);

    render(selectFieldFilterType.getFormNode());

    await user.click(screen.getByLabelText('test'));

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

    render(selectFieldFilterType.getFormNode());

    const checkboxes = screen.getAllByRole('checkbox');

    expect(checkboxes).toHaveLength(3);
    expectCheckboxValue('app.job.jobSource.0', '0');
    expectCheckboxValue('app.job.jobSource.1', '1');
    expectCheckboxValue('app.job.jobSource.2', '2');
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
