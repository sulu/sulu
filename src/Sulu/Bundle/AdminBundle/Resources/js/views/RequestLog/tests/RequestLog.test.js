// @flow

import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom/extend-expect';
import symfonyRouting from 'fos-jsrouting/router';
import Requester from '../../../services/Requester';
import {translate} from '../../../utils/Translator';
import RequestLog from '../RequestLog';

jest.mock('fos-jsrouting/router');
jest.mock('../../../services/Requester', () => ({
    get: jest.fn(),
}));
jest.mock('../../../utils/Translator');
jest.mock('../../../containers/Toolbar', () => ({
    withToolbar: (Component) => Component,
}));

const detailPayload = {
    chain: [
        {
            annotations: [],
            content: 'You are an SEO expert.',
            contentType: 'text',
            title: 'sulu_ai_platform.request_log.chain.role.system',
            type: 'system',
        },
        {
            annotations: [],
            content: 'Optimize this page.',
            contentType: 'text',
            title: 'sulu_ai_platform.request_log.chain.role.user',
            type: 'user',
        },
        {
            annotations: [
                'sulu_ai_platform.request_log.chain.duration:1.2',
                'sulu_ai_platform.request_log.chain.credits:3',
            ],
            content: null,
            contentType: 'text',
            title: 'sulu_ai_platform.request_log.chain.response',
            type: 'response',
        },
    ],
    credits: 3,
    durationMs: 1200,
    errorCode: null,
    errorMessage: null,
    feature: 'seo_generator',
    id: 42,
    locale: 'en',
    model: 'gemini-2.5-flash',
    status: 'succeeded',
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
    symfonyRouting.generate.mockImplementation((route, params) => '/' + route + '/' + (params?.id ?? ''));

    // $FlowFixMe
    translate.mockImplementation((key) => key);
});

test('creates the list store against the request_log resource and list key', () => {
    const {ListStore} = require('../../../containers/List');

    // $FlowFixMe
    render(<RequestLog />);

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
    Requester.get.mockReturnValue(Promise.resolve(detailPayload));

    // $FlowFixMe
    render(<RequestLog />);

    await userEvent.click(screen.getByText('open-item'));

    expect(Requester.get).toHaveBeenCalledWith('/sulu_ai_platform.request_log_detail/42');
    expect(await screen.findByText('You are an SEO expert.')).toBeInTheDocument();
    expect(screen.getByText('Optimize this page.')).toBeInTheDocument();
    expect(screen.getByText(
        'sulu_ai_platform.request_log.chain.duration:1.2 · sulu_ai_platform.request_log.chain.credits:3'
    )).toBeInTheDocument();
});

test('renders an error message when loading the detail fails', async() => {
    Requester.get.mockReturnValue(Promise.reject(new Error('failed')));

    // $FlowFixMe
    render(<RequestLog />);

    await userEvent.click(screen.getByText('open-item'));

    expect(await screen.findByText('sulu_ai_platform.request_log.detail_error')).toBeInTheDocument();
});
