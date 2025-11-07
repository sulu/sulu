// @flow
import {removePTags, addPTags} from '../utils';

test('Test remove p tags', () => {
    const html = '<p>This is a paragraph.</p>';
    const expected = 'This is a paragraph.';
    expect(removePTags(html)).toBe(expected);
});

test('Test remove p tags complex', () => {
    const html = [
        '<h2>Headline</h2>',
        '<p>paragraph with Link',
        '<a target="_self" href="https://www.sulu.io">',
        'https://www.sulu.io',
        '</a>',
        ' test 213</p>',
    ].join('');
    const expected = [
        '<h2>Headline</h2>',
        '<!--p-->paragraph with Link',
        '<a target="_self" href="https://www.sulu.io">',
        'https://www.sulu.io',
        '</a>',
        ' test 213<!--/p-->',
    ].join('');
    expect(removePTags(html)).toBe(expected);
});

test('Test remove multiple p tages', () => {
    const html = '<p>Test line 1</p><p>Test line 2</p><p>Test line 3</p>';
    const expected = [
        '<!--p-->Test line 1<!--/p-->',
        '<br></br>',
        '<!--p-->Test line 2<!--/p-->',
        '<br></br>',
        '<!--p-->Test line 3<!--/p-->',
    ].join('');
    expect(removePTags(html)).toBe(expected);
});

test('Test readd p tags', () => {
    const string = 'This is a paragraph.';
    const html = '<p>This is a paragraph.</p>';
    expect(addPTags(string)).toBe(html);
});

test('Test readd p tags complex', () => {
    const string = [
        '<h2>Headline</h2>',
        '<!--p-->paragraph with Link',
        '<a target="_self" href="https://www.sulu.io">',
        'https://www.sulu.io',
        '</a>',
        ' test 213<!--/p-->',
    ].join('');
    const html = [
        '<h2>Headline</h2>',
        '<p>paragraph with Link',
        '<a target="_self" href="https://www.sulu.io">',
        'https://www.sulu.io',
        '</a>',
        ' test 213</p>',
    ].join('');
    expect(addPTags(string)).toBe(html);
});

test('Test readd multiple p tages', () => {
    const string = [
        '<!--p-->Test line 1<!--/p-->',
        '<br></br>',
        '<!--p-->Test line 2<!--/p-->',
        '<br></br>',
        '<!--p-->Test line 3<!--/p-->',
    ].join('');
    const html = [
        '<p>Test line 1</p>',
        '<p>Test line 2</p>',
        '<p>Test line 3</p>',
    ].join('');
    expect(addPTags(string)).toBe(html);
});
