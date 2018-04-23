// @flow
import log from 'loglevel';
import moment from 'moment-timezone';
import TimeFieldTransformer from '../../fieldTransformers/TimeFieldTransformer';

const timeFieldTransformer = new TimeFieldTransformer();

beforeEach(() => {
    moment.tz.setDefault('Europe/Vienna');
});

jest.mock('loglevel', () => ({
    error: jest.fn(),
}));

test('Test undefined', () => {
    expect(timeFieldTransformer.transform(undefined)).toBe(undefined);
});

test('Test invalid format', () => {
    expect(timeFieldTransformer.transform('xxx')).toBe(undefined);
    expect(log.error).toBeCalledWith('Invalid date given: "xxx". Format needs to be in "ISO 8601"');
});

test('Test valid example', () => {
    expect(timeFieldTransformer.transform('2018-03-10T14:09:04+01:00')).toBe('2:09 PM');
});

