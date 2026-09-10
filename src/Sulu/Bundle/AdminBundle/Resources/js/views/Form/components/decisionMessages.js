// @flow
import {translate} from '../../../utils/Translator';
import type {DecisionMessage} from './types';

// The server sends keys, not sentences: only the admin knows the user's locale. Raw text is what no
// fixed key could express, like the ids a check found.
export function renderMessages(messages: Array<DecisionMessage>): ?string {
    const rendered = (messages || [])
        .map((message) => message.key ? translate(message.key, message.parameters) : message.text)
        .filter(Boolean);

    return rendered.length > 0 ? rendered.join(' ') : null;
}
