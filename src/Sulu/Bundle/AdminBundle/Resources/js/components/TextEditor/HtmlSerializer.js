// @flow
import React from 'react';
import Html from 'slate-html-serializer';

const BLOCK_TAGS = {
    blockquote: 'quote',
    p: 'paragraph',
    br: 'break',
    pre: 'code',
    ol: 'ordered-list',
    ul: 'unordered-list',
    li: 'list-item',
};

// Add a dictionary of mark tags.
const MARK_TAGS = {
    strong: 'bold',
    em: 'italic',
    u: 'underline',
    s: 'strikethrough',
};

const rules = [
    {
        deserialize(el, next) {
            const type = BLOCK_TAGS[el.tagName.toLowerCase()];

            if (!type) {
                return;
            }

            return {
                object: 'block',
                type: type,
                nodes: next(el.childNodes),
            };
        },
        serialize(obj, children) {
            if (obj.object != 'block') {
                return;
            }

            switch (obj.type) {
                case 'code':
                    return (
                        <pre>
                            <code>{children}</code>
                        </pre>
                    );
                case 'paragraph':
                    return <p>{children}</p>;
                case 'break':
                    return <br/>;
                case 'quote':
                    return <blockquote>{children}</blockquote>;
                case 'ordered-list':
                    return <ol>{children}</ol>;
                case 'unordered-list':
                    return <ul>{children}</ul>;
                case 'list-item':
                    return <li>{children}</li>;
            }
        },
    },
    // Add a new rule that handles marks...
    {
        deserialize(el, next) {
            const type = MARK_TAGS[el.tagName.toLowerCase()];

            if (!type) {
                return;
            }

            return {
                object: 'mark',
                type: type,
                nodes: next(el.childNodes),
            };
        },
        serialize(obj, children) {
            if (obj.object != 'mark') {
                return;
            }

            switch (obj.type) {
                case 'bold':
                    return <strong>{children}</strong>;
                case 'italic':
                    return <em>{children}</em>;
                case 'underline':
                    return <u>{children}</u>;
                case 'strikethrough':
                    return <s>{children}</s>;
            }
        },
    },
];

export default new Html({rules});
