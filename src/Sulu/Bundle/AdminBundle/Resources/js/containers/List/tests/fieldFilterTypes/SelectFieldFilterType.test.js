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

test('Pass correct props to CheckboxGroup if the values are numbers', () => {
    const selectFieldFilterType = new SelectFieldFilterType(
        jest.fn(),
        {options: {'1': 'Open', '2': 'Approved', '3': 'Rejected'}},
        // $FlowFixMe: numeric option keys are restored as numbers from the URL
        [1, 3]
    );

    render(selectFieldFilterType.getFormNode());

    expectCheckboxValue('Open', '1');
    expect(screen.getByLabelText('Open')).toBeChecked();
    expectCheckboxValue('Approved', '2');
    expect(screen.getByLabelText('Approved')).not.toBeChecked();
    expectCheckboxValue('Rejected', '3');
    expect(screen.getByLabelText('Rejected')).toBeChecked();
});

test('Call onChange handler without duplicates if the values are numbers', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const selectFieldFilterType = new SelectFieldFilterType(
        changeSpy,
        {options: {'1': 'Open', '2': 'Approved', '3': 'Rejected'}},
        // $FlowFixMe: numeric option keys are restored as numbers from the URL
        [1, 3]
    );

    render(selectFieldFilterType.getFormNode());

    await user.click(screen.getByLabelText('Approved'));
    expect(changeSpy).toHaveBeenLastCalledWith(['1', '3', '2']);

    await user.click(screen.getByLabelText('Open'));
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

    render(selectFieldFilterType.getFormNode());

    expect(screen.getByLabelText('Open')).toBeChecked();
    expect(screen.getByLabelText('Approved')).not.toBeChecked();
    expect(screen.getByLabelText('Rejected')).toBeChecked();
});
