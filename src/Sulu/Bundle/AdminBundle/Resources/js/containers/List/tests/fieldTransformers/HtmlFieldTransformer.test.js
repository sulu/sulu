// @flow
import {render} from '@testing-library/react';
import HtmlFieldTransformer from '../../fieldTransformers/HtmlFieldTransformer';

const htmlTransformer = new HtmlFieldTransformer();

test('Test undefined', () => {
    expect(htmlTransformer.transform(undefined)).toBe(null);
});

test('Test string', () => {
    const view = render(htmlTransformer.transform('test string'));

    expect(view.container.innerHTML).toEqual('<div>test string</div>');
});

test('Test number', () => {
    const view = render(htmlTransformer.transform(5));

    expect(view.container.innerHTML).toEqual('<div>5</div>');
});

test('Test html string with allowed tags', () => {
    const view = render(htmlTransformer.transform('I am a <b>bold</b> and <i>italic</i>'));

    expect(view.container.innerHTML).toEqual('<div>I am a <b>bold</b> and <i>italic</i></div>');
});

test('Test html string with disallowed tags', () => {
    const view = render(htmlTransformer.transform(
        'Unwanted and dangerous <table>tags</table> are <u>sanitized</u> <script>console.log("muhahaha")</script>'
    ));

    expect(view.container.innerHTML).toEqual(
        // eslint-disable-next-line max-len
        '<div>Unwanted and dangerous &lt;table&gt;tags&lt;/table&gt; are <u>sanitized</u> &lt;script&gt;console.log("muhahaha")&lt;/script&gt;</div>'
    );
});
