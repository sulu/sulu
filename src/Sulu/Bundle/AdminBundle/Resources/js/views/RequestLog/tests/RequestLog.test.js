// @flow

import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom/extend-expect';
import ResourceRequester from '../../../services/ResourceRequester';
import {translate} from '../../../utils/Translator';
import RequestLog from '../RequestLog';

jest.mock('../../../services/ResourceRequester', () => ({
    get: jest.fn(),
}));
jest.mock('../../../utils/Translator');
jest.mock('../../../containers/Toolbar', () => ({
    withToolbar: (Component) => Component,
}));

const router = {
    route: {
        options: {
            translationPrefix: 'test_request_log.',
        },
    },
};

const detailPayload = {
    chain: [
        {
            annotations: [],
            content: 'You are an SEO expert.',
            contentType: 'text',
            title: 'test_request_log.chain.role.system',
            type: 'system',
        },
        {
            annotations: [],
            content: 'Optimize this page.',
            contentType: 'text',
            title: 'test_request_log.chain.role.user',
            type: 'user',
        },
        {
            annotations: [
                'test_request_log.chain.duration:1.2',
                'test_request_log.chain.credits:3',
            ],
            content: null,
            contentType: 'text',
            title: 'test_request_log.chain.response',
            type: 'response',
        },
    ],
    credits: 3,
    durationMs: 1200,
    endpoint: 'POST /chat',
    errorCode: null,
    errorMessage: null,
    expertKey: 'seo_generator',
    expertName: 'SEO Expert',
    feature: 'seo_generator',
    id: 42,
    locale: 'en',
    model: 'gemini-2.5-flash',
    requestType: 'chat',
    startedAt: '2026-09-18T07:41:12+00:00',
    status: 'succeeded',
    streamed: false,
    trigger: null,
    uuid: 'd41f8a02-6b1c-4e8e-9a77-3c92f1c40b11',
    webspaceKey: null,
};

jest.mock('../../../containers/List', () => {
    const MockReact = require('react');

    class MockList extends MockReact.Component<{onItemClick: (number) => void}> {
        requestSelectionDelete = jest.fn();

        handleClick = () => {
            this.props.onItemClick(42);
        };

        render() {
            return (
                <button onClick={this.handleClick} type="button">
                    open-item
                </button>
            );
        }
    }

    return {
        __esModule: true,
        default: MockList,
        ListStore: jest.fn(function() {
            this.destroy = jest.fn();
            this.reload = jest.fn();
            this.selectionIds = [];
            this.deletingSelection = false;
        }),
    };
});

beforeEach(() => {
    // $FlowFixMe
    translate.mockImplementation((key) => key);
});

test('creates the list store against the request_log resource and list key', () => {
    const {ListStore} = require('../../../containers/List');

    // $FlowFixMe
    render(<RequestLog router={router} />);

    expect(ListStore).toHaveBeenCalledWith(
        'request_log',
        'request_log',
        'request_log',
        expect.any(Object),
        {},
        {}
    );
});

test('loads and renders the detail overlay when a row is clicked', async() => {
    ResourceRequester.get.mockReturnValue(Promise.resolve(detailPayload));

    // $FlowFixMe
    render(<RequestLog router={router} />);

    await userEvent.click(screen.getByText('open-item'));

    expect(ResourceRequester.get).toHaveBeenCalledWith('request_log', {id: 42});
    expect(await screen.findByText('You are an SEO expert.')).toBeInTheDocument();
    expect(screen.getByText('Optimize this page.')).toBeInTheDocument();
    expect(screen.getByText(
        'test_request_log.chain.duration:1.2 · test_request_log.chain.credits:3'
    )).toBeInTheDocument();
    expect(screen.getByText('test_request_log.request_type.chat')).toBeInTheDocument();
    expect(screen.getByText('SEO Expert')).toBeInTheDocument();
    expect(screen.getByText('d41f8a02-6b1c-4e8e-9a77-3c92f1c40b11')).toBeInTheDocument();
    expect(screen.getByText('test_request_log.status.succeeded')).toBeInTheDocument();
});

test('indents a message that is JSON and collapses long content', async() => {
    const context = {content: {title: 'A', blocks: [1, 2, 3, 4, 5, 6], article: 'x'.repeat(50)}};
    ResourceRequester.get.mockReturnValue(Promise.resolve({
        ...detailPayload,
        chain: [{
            annotations: [],
            content: JSON.stringify(context),
            contentType: 'text',
            title: 'test_request_log.chain.role.user',
            type: 'user',
        }],
    }));

    // $FlowFixMe
    render(<RequestLog router={router} />);

    await userEvent.click(screen.getByText('open-item'));

    expect(await screen.findByText(JSON.stringify(context, null, 2), {normalizer: (text) => text}))
        .toBeInTheDocument();
    await userEvent.click(screen.getByText('test_request_log.show_full'));
    expect(screen.getByText('test_request_log.show_less')).toBeInTheDocument();
});

test('leaves markdown with citations untouched', async() => {
    const answer = 'It is a test page.[[1]](https://example.com/a) See also [2](https://example.com/b).';
    ResourceRequester.get.mockReturnValue(Promise.resolve({
        ...detailPayload,
        chain: [{annotations: [], content: answer, contentType: 'text', title: 'Response', type: 'response'}],
    }));

    // $FlowFixMe
    render(<RequestLog router={router} />);

    await userEvent.click(screen.getByText('open-item'));

    expect(await screen.findByText(answer)).toBeInTheDocument();
});

test('shows a translation as original next to result', async() => {
    ResourceRequester.get.mockReturnValue(Promise.resolve({
        ...detailPayload,
        chain: [
            {annotations: [], content: 'Hello world', contentType: 'text', title: 'User', type: 'user'},
            {annotations: [], content: 'Hallo Welt', contentType: 'text', title: 'Response', type: 'response'},
        ],
        expertKey: null,
        expertName: null,
        model: null,
        provider: 'deepl',
        requestType: 'translation',
    }));

    // $FlowFixMe
    render(<RequestLog router={router} />);

    await userEvent.click(screen.getByText('open-item'));

    expect(await screen.findByText('Hello world')).toBeInTheDocument();
    expect(screen.getByText('Hallo Welt')).toBeInTheDocument();
    expect(screen.getByText('test_request_log.translation_original')).toBeInTheDocument();
    expect(screen.getByText('test_request_log.translation_result')).toBeInTheDocument();
    expect(screen.getByText('deepl')).toBeInTheDocument();
    expect(screen.queryByText('test_request_log.expert_key_label')).not.toBeInTheDocument();
});

test('merges a writeback step into the preceding response card instead of its own step', async() => {
    ResourceRequester.get.mockReturnValue(Promise.resolve({
        ...detailPayload,
        chain: [
            {annotations: [], content: 'Optimize this page.', contentType: 'text', title: 'User', type: 'user'},
            {annotations: [], content: 'Done.', contentType: 'text', title: 'Response', type: 'response'},
            {
                annotations: [],
                content: null,
                contentType: 'text',
                title: 'test_request_log.chain.writeback',
                type: 'writeback',
            },
        ],
    }));

    // $FlowFixMe
    render(<RequestLog router={router} />);

    await userEvent.click(screen.getByText('open-item'));

    expect(await screen.findByText('test_request_log.chain.writeback')).toBeInTheDocument();
    expect(screen.getAllByRole('listitem')).toHaveLength(2);
});

test('shows the error banner with the error code and message for a failed request', async() => {
    ResourceRequester.get.mockReturnValue(Promise.resolve({
        ...detailPayload,
        status: 'failed',
        errorCode: 'RATE_LIMIT',
        errorMessage: 'Too many requests.',
    }));

    // $FlowFixMe
    render(<RequestLog router={router} />);

    await userEvent.click(screen.getByText('open-item'));

    expect(await screen.findByText('- RATE_LIMIT: Too many requests.')).toBeInTheDocument();
});

test('renders an error message when loading the detail fails', async() => {
    ResourceRequester.get.mockReturnValue(Promise.reject(new Error('failed')));

    // $FlowFixMe
    render(<RequestLog router={router} />);

    await userEvent.click(screen.getByText('open-item'));

    expect(await screen.findByText('test_request_log.detail_error')).toBeInTheDocument();
});
