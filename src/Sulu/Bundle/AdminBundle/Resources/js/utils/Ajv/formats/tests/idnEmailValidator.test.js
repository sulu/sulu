// @flow
import idnEmailValidator from '../idnEmailValidator';

test('Normal email address should pass validation', () => {
    expect(idnEmailValidator('hello@example.com')).toBe(true);
});

test('Email address with emojis should pass validation', () => {
    expect(idnEmailValidator('🤌@😎')).toBe(true);
});

test('Invalid email address must not pass validation', () => {
    expect(idnEmailValidator('invalid')).toBe(false);
});
