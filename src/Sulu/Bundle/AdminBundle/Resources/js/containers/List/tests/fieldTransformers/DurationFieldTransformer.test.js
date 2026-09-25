// @flow
import DurationFieldTransformer from '../../fieldTransformers/DurationFieldTransformer';

const durationFieldTransformer = new DurationFieldTransformer();

test('Test undefined and null', () => {
    expect(durationFieldTransformer.transform(undefined)).toBe(null);
    expect(durationFieldTransformer.transform(null)).toBe(null);
});

test('Test values under a second are shown in milliseconds', () => {
    expect(durationFieldTransformer.transform(0)).toBe('0ms');
    expect(durationFieldTransformer.transform(20)).toBe('20ms');
});

test('Test values under a minute are shown as seconds with one decimal', () => {
    expect(durationFieldTransformer.transform(2231)).toBe('2.2s');
    expect(durationFieldTransformer.transform('1500')).toBe('1.5s');
});

test('Test values under an hour are shown as minutes and seconds', () => {
    expect(durationFieldTransformer.transform(90000)).toBe('1m 30s');
    expect(durationFieldTransformer.transform(120000)).toBe('2m');
});

test('Test values of an hour or more are shown as hours and minutes', () => {
    expect(durationFieldTransformer.transform(90 * 60 * 1000)).toBe('1h 30m');
    expect(durationFieldTransformer.transform(2 * 60 * 60 * 1000)).toBe('2h');
});

test('Test values are rounded to whole seconds before being split into minutes and hours', () => {
    expect(durationFieldTransformer.transform(119700)).toBe('2m');
    expect(durationFieldTransformer.transform(59999)).toBe('1m');
    expect(durationFieldTransformer.transform(999.6)).toBe('1.0s');
});
