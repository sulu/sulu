// @flow
import ibanValidator from '../ibanValidator';

test('IBAN with spaces should pass validation', () => {
    expect(ibanValidator('AT61 1904 3002 3457 3201')).toBe(true);
});

test('IBAN without spaces should pass validation', () => {
    expect(ibanValidator('AT611904300234573201')).toBe(true);
});

test('Invalid IBAN must not pass validation', () => {
    expect(ibanValidator('invalid')).toBe(false);
});
