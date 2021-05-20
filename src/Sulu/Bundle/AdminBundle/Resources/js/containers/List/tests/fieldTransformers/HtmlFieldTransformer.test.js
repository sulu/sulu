// @flow
import {mount} from 'enzyme';
import HtmlFieldTransformer from '../../fieldTransformers/HtmlFieldTransformer';

const htmlTransformer = new HtmlFieldTransformer();

test('Test undefined', () => {
    expect(htmlTransformer.transform(undefined)).toBe(null);
});

test('Test string', () => {
    const result = mount(htmlTransformer.transform('test string'));

    expect(result.html()).toEqual('<div>test string</div>');
});

test('Test number', () => {
    const result = mount(htmlTransformer.transform(5));

    expect(result.html()).toEqual('<div>5</div>');
});

test('Test html string with allowed tags', () => {
    const result = mount(htmlTransformer.transform('I am a <b>bold</b> and <i>italic</i>'));

    expect(result.html()).toEqual('<div>I am a <b>bold</b> and <i>italic</i></div>');
});

test('Test html string with disallowed tags', () => {
    const result = mount(htmlTransformer.transform(
        'Unwanted and dangerous <table>tags</table> are <u>sanitized</u> <script>console.log("muhahaha")</script>'
    ));

    expect(result.html()).toEqual(
        // eslint-disable-next-line max-len
        '<div>Unwanted and dangerous &lt;table&gt;tags&lt;/table&gt; are <u>sanitized</u> &lt;script&gt;console.log("muhahaha")&lt;/script&gt;</div>'
    );
});
