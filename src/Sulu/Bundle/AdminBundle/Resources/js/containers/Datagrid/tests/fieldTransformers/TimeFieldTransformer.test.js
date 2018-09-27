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
    expect(timeFieldTransformer.transform(undefined)).toBe(null);
});

test('Test invalid format', () => {
    expect(timeFieldTransformer.transform('xxx')).toBe(null);
    expect(log.error).toBeCalledWith('Invalid time given: "xxx". Format needs to be "HH:mm:ss"');
});

test('Test valid example', () => {
    expect(timeFieldTransformer.transform('14:09')).toBe('2:09 PM');
});
