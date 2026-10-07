// @flow
import {removePTags, addPTags} from '../utils';

test('Test remove p tags', () => {
    const html = '<p>This is a paragraph.</p>';
    const expected = 'This is a paragraph.';
    expect(removePTags(html)).toBe(expected);
});

test('Test remove p tags from a paragraph holding a line break', () => {
    expect(removePTags('<p>one<br>two</p>')).toBe('one<br>two');
});

test('Test remove p tags from a paragraph holding inline markup', () => {
    expect(removePTags('<p>a <strong>bold</strong> word</p>')).toBe('a <strong>bold</strong> word');
});

test('Test remove p tags from a paragraph carrying attributes', () => {
    expect(removePTags('<p dir="rtl" class="intro">a <strong>bold</strong> word</p>'))
        .toBe('a <strong>bold</strong> word');
});

test('Test remove p tags from several paragraphs carrying attributes', () => {
    expect(removePTags('<p style="color:red">one</p><p class="intro">two</p>'))
        .toBe('<!--p-->one<!--/p--><br></br><!--p-->two<!--/p-->');
});

test('Test remove p tags from a paragraph carrying a text alignment', () => {
    expect(removePTags('<p style="text-align:center;">centered</p>')).toBe('centered');
});

test('Test remove p tags from several paragraphs carrying a text alignment', () => {
    expect(removePTags('<p style="text-align:center;">one</p><p style="text-align:right;">two</p>'))
        .toBe('<!--p-->one<!--/p--><br></br><!--p-->two<!--/p-->');
});

test('Test keep the style of an inline element when removing p tags', () => {
    expect(removePTags('<p style="text-align:center;">a <span style="color:red;">red</span> word</p>'))
        .toBe('a <span style="color:red;">red</span> word');
    expect(removePTags('<p style="text-align:center;">one</p><p>a <span style="color:red;">red</span></p>'))
        .toBe('<!--p-->one<!--/p--><br></br><!--p-->a <span style="color:red;">red</span><!--/p-->');
});

test('Test no paragraph style survives removing and readding p tags', () => {
    expect(addPTags(removePTags('<p style="text-align:center;">one</p>'))).toBe('<p>one</p>');
    expect(addPTags(removePTags('<p style="text-align:center;">one</p><p dir="rtl" class="intro">two</p>')))
        .toBe('<p>one</p><p>two</p>');
});

test('Test readd p tags to a paragraph holding a line break', () => {
    expect(addPTags('one<br>two')).toBe('<p>one<br>two</p>');
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
