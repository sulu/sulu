// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import SelectFieldFilterType from '../../fieldFilterTypes/SelectFieldFilterType';

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
    render(selectFieldFilterType.getFormNode());

    Object.entries(parameters.options).forEach(([optionKey, optionLabel]) => {
        const checkbox = screen.getByLabelText(optionLabel);
        expect((checkbox: any).value).toEqual(optionKey);

        if (value && value.includes(optionKey)) {
            expect(checkbox).toBeChecked();
        } else {
            expect(checkbox).not.toBeChecked();
        }
    });
});

test('Render with value set by setValue', () => {
    const selectFieldFilterType = new SelectFieldFilterType(
        jest.fn(),
        {options: {audio: 'sulu_media.audio'}},
        undefined
    );

    selectFieldFilterType.setValue(['audio']);
    render(selectFieldFilterType.getFormNode());

    expect(screen.getByLabelText('sulu_media.audio')).toBeChecked();
});

test('Pass correct props to CheckboxGroup', () => {
    const selectFieldFilterType = new SelectFieldFilterType(
        jest.fn(),
        {options: {audio: 'Audio', image: 'Image', video: 'Video'}},
        ['audio', 'video']
    );

    render(selectFieldFilterType.getFormNode());

    const allCheckboxes = screen.getAllByRole('checkbox');
    expect(allCheckboxes).toHaveLength(3);

    expect((screen.getByLabelText('Audio'): any).value).toEqual('audio');
    expect(screen.getByLabelText('Audio')).toBeChecked();
    expect((screen.getByLabelText('Image'): any).value).toEqual('image');
    expect(screen.getByLabelText('Image')).not.toBeChecked();
    expect((screen.getByLabelText('Video'): any).value).toEqual('video');
    expect(screen.getByLabelText('Video')).toBeChecked();
});

test('Call onChange handler with new value', async() => {
    const changeSpy = jest.fn();
    const selectFieldFilterType = new SelectFieldFilterType(changeSpy, {options: {test: 'test'}}, undefined);
    const user = userEvent.setup();

    render(selectFieldFilterType.getFormNode());
    await user.click(screen.getByLabelText('test'));

    expect(changeSpy).toBeCalledWith(['test']);
});

test('Call onChange handler with undefined if the new selection is empty', async() => {
    const changeSpy = jest.fn();
    const selectFieldFilterType = new SelectFieldFilterType(changeSpy, {options: {test: 'test'}}, ['test']);
    const user = userEvent.setup();

    render(selectFieldFilterType.getFormNode());
    await user.click(screen.getByLabelText('test'));

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

    render(selectFieldFilterType.getFormNode());

    const allCheckboxes = screen.getAllByRole('checkbox');
    expect(allCheckboxes).toHaveLength(3);

    expect((screen.getByLabelText('app.job.jobSource.0'): any).value).toEqual('0');
    expect((screen.getByLabelText('app.job.jobSource.1'): any).value).toEqual('1');
    expect((screen.getByLabelText('app.job.jobSource.2'): any).value).toEqual('2');
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
