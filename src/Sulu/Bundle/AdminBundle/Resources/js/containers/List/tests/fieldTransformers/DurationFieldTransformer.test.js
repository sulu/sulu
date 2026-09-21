// @flow
import DurationFieldTransformer from '../../fieldTransformers/DurationFieldTransformer';

const durationFieldTransformer = new DurationFieldTransformer();

test('Test undefined and null', () => {
    expect(durationFieldTransformer.transform(undefined)).toBe(null);
    expect(durationFieldTransformer.transform(null)).toBe(null);
});

test('Test milliseconds are shown as seconds with one decimal', () => {
    expect(durationFieldTransformer.transform(0)).toBe('0.0s');
    expect(durationFieldTransformer.transform(2231)).toBe('2.2s');
    expect(durationFieldTransformer.transform('1500')).toBe('1.5s');
});
