// @flow
import bicValidator from '../bicValidator';

test('BIC with spaces should pass validation', () => {
    expect(bicValidator('RVVGAT2B475')).toBe(true);
});

test('Invalid BIC must not pass validation', () => {
    expect(bicValidator('invalid')).toBe(false);
});
