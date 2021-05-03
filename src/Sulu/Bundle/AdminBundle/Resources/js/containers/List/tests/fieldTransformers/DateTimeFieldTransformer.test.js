// @flow
import React from 'react';
import log from 'loglevel';
import moment from 'moment-timezone';
import DateTimeFieldTransformer from '../../fieldTransformers/DateTimeFieldTransformer';
import {translate} from '../../../../utils';

beforeEach(() => {
    moment.tz.setDefault('Europe/Vienna');
});

const dateTimeFieldTransformer = new DateTimeFieldTransformer();

jest.mock('loglevel', () => ({
    error: jest.fn(),
}));

jest.mock('../../../../utils', () => ({
    translate: jest.fn((key) => key),
}));

test('Test undefined', () => {
    expect(dateTimeFieldTransformer.transform(undefined, {})).toBe(null);
});

test('Test invalid format', () => {
    expect(dateTimeFieldTransformer.transform('xxx', {})).toBe(null);
    expect(log.error).toBeCalledWith('Invalid date given: "xxx". Format needs to be in "ISO 8601"');
});

test('Test valid example', () => {
    expect(
        dateTimeFieldTransformer.transform('2018-03-10T14:09:04+01:00', {})
    ).toEqual(<span className="dateTime default">March 10, 2018 2:09 PM</span>);
});

test('Test light skin example', () => {
    expect(
        dateTimeFieldTransformer.transform('2018-03-10T14:09:04+01:00', {'skin': 'light'})
    ).toEqual(<span className="dateTime light">March 10, 2018 2:09 PM</span>);
});

test('Test invalid skin type', () => {
    dateTimeFieldTransformer.transform('2018-03-10T14:09:04+01:00', {'skin': 123});

    expect(log.error).toBeCalledWith('Transformer parameter "skin" needs to be of type string, number given.');
});

test('Test relative format sameDay example', () => {
    const dateTime = dateTimeFieldTransformer.transform(moment(), {format: 'relative'});

    // $FlowFixMe
    expect(dateTime.props.children).toContain('sulu_admin.sameDay');
    expect(translate).toHaveBeenCalledWith('sulu_admin.sameDay');
});

test('Test relative format nextDay example', () => {
    const dateTime = dateTimeFieldTransformer.transform(moment().add(1, 'day'), {format: 'relative'});

    // $FlowFixMe
    expect(dateTime.props.children).toContain('sulu_admin.nextDay');
    expect(translate).toHaveBeenCalledWith('sulu_admin.nextDay');
});

test('Test relative format lastDay example', () => {
    const dateTime = dateTimeFieldTransformer.transform(moment().subtract(1, 'day'), {format: 'relative'});

    // $FlowFixMe
    expect(dateTime.props.children).toContain('sulu_admin.lastDay');
    expect(translate).toHaveBeenCalledWith('sulu_admin.lastDay');
});

test('Test relative format lastWeek example', () => {
    const momentObject = moment().subtract(7, 'day');
    const dateTime = dateTimeFieldTransformer.transform(momentObject, {format: 'relative'});

    // $FlowFixMe
    expect(dateTime.props.children).toContain(momentObject.format('LLL'));
    expect(translate).toHaveBeenCalledWith('sulu_admin.lastDay');
});
