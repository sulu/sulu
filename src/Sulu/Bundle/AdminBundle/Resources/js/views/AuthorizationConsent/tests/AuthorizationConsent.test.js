/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import symfonyRouting from 'fos-jsrouting/router';
import Requester from '../../../services/Requester';
import AuthorizationConsent from '../AuthorizationConsent';

jest.mock('fos-jsrouting/router', () => ({
    generate: jest.fn(),
}));

jest.mock('../../../services/Requester', () => ({
    get: jest.fn(),
    post: jest.fn(),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key, parameters) => parameters && parameters.clientName
        ? key + ':' + parameters.clientName
        : key
    ),
}));

jest.mock('../../../components/Snackbar', () => {
    const React = require('react');

    return function Snackbar(props) {
        return React.createElement('div', {className: 'snackbar'}, props.message);
    };
});

const route = {
    options: {
        detailsRoute: 'sulu_mcp_server_oauth_consent_details',
        decisionRoute: 'sulu_mcp_server_oauth_consent_decision',
    },
};

const router = {
    attributes: {
        requestId: 'request-1',
    },
};

const consentDetails = {
    clientName: 'ChatGPT',
    redirectUri: 'https://chatgpt.com/oauth/callback',
    scopes: [
        {
            id: 'mcp:tools',
            label: 'Use MCP tools',
        },
    ],
};

function getDenyButton() {
    return screen.getByRole('button', {name: 'sulu_admin.authorization_consent_deny'});
}

function getApproveButton() {
    return screen.getByRole('button', {name: 'sulu_admin.authorization_consent_approve'});
}

beforeEach(() => {
    Requester.get.mockReset();
    Requester.post.mockReset();
    symfonyRouting.generate.mockReset();
    symfonyRouting.generate.mockImplementation(
        (routeName, parameters) => '/' + routeName + '/' + parameters.requestId
    );
    window.location.assign = jest.fn();
});

test('Should load and render authorization consent details', async() => {
    Requester.get.mockReturnValue(Promise.resolve(consentDetails));

    render(<AuthorizationConsent route={route} router={router} />);

    expect(symfonyRouting.generate).toHaveBeenCalledWith(
        'sulu_mcp_server_oauth_consent_details',
        {requestId: 'request-1'}
    );
    expect(Requester.get).toHaveBeenCalledWith('/sulu_mcp_server_oauth_consent_details/request-1');

    expect(await screen.findByText('ChatGPT')).toBeInTheDocument();
    expect(screen.getByText('Use MCP tools')).toBeInTheDocument();
    expect(screen.getByText('https://chatgpt.com/oauth/callback')).toBeInTheDocument();
});

test('Should approve authorization consent and redirect to continuation URL', async() => {
    const user = userEvent.setup();
    Requester.get.mockReturnValue(Promise.resolve(consentDetails));
    Requester.post.mockReturnValue(Promise.resolve({redirectUrl: '/admin/mcp/authorize?sulu_mcp_consent=request-1'}));

    render(<AuthorizationConsent route={route} router={router} />);

    expect(await screen.findByText('ChatGPT')).toBeInTheDocument();
    await user.click(getApproveButton());

    expect(symfonyRouting.generate).toHaveBeenCalledWith(
        'sulu_mcp_server_oauth_consent_decision',
        {requestId: 'request-1'}
    );
    expect(Requester.post).toHaveBeenCalledWith('/sulu_mcp_server_oauth_consent_decision/request-1', {approved: true});
    expect(window.location.assign).toHaveBeenCalledWith('/admin/mcp/authorize?sulu_mcp_consent=request-1');
});

test('Should deny authorization consent and redirect to continuation URL', async() => {
    const user = userEvent.setup();
    Requester.get.mockReturnValue(Promise.resolve(consentDetails));
    Requester.post.mockReturnValue(Promise.resolve({redirectUrl: '/admin/mcp/authorize?sulu_mcp_consent=request-1'}));

    render(<AuthorizationConsent route={route} router={router} />);

    expect(await screen.findByText('ChatGPT')).toBeInTheDocument();
    await user.click(getDenyButton());

    expect(Requester.post).toHaveBeenCalledWith('/sulu_mcp_server_oauth_consent_decision/request-1', {approved: false});
    expect(window.location.assign).toHaveBeenCalledWith('/admin/mcp/authorize?sulu_mcp_consent=request-1');
});

test('Should show an error and re-enable the buttons when the decision cannot be submitted', async() => {
    const user = userEvent.setup();
    Requester.get.mockReturnValue(Promise.resolve(consentDetails));
    Requester.post.mockImplementation(() => Promise.reject(new Error('Server error')));

    render(<AuthorizationConsent route={route} router={router} />);

    expect(await screen.findByText('ChatGPT')).toBeInTheDocument();
    await user.click(getApproveButton());

    expect(await screen.findByText('sulu_admin.authorization_consent_decision_error')).toBeInTheDocument();
    expect(window.location.assign).not.toHaveBeenCalled();
    expect(getDenyButton()).toBeEnabled();
    expect(getApproveButton()).toBeEnabled();
});

test('Should disable both buttons and show a loader while the decision is being submitted', async() => {
    const user = userEvent.setup();
    Requester.get.mockReturnValue(Promise.resolve(consentDetails));
    const pendingDecisionRequest = new Promise(() => {});
    pendingDecisionRequest.abort = jest.fn();
    Requester.post.mockReturnValue(pendingDecisionRequest);

    render(<AuthorizationConsent route={route} router={router} />);

    expect(await screen.findByText('ChatGPT')).toBeInTheDocument();
    await user.click(getApproveButton());

    expect(Requester.post).toHaveBeenCalledTimes(1);
    expect(getDenyButton()).toBeDisabled();
    expect(getDenyButton()).not.toHaveClass('loading');
    expect(getApproveButton()).toBeDisabled();
    expect(getApproveButton()).toHaveClass('loading');
});

test('Should render an error message when the consent details are malformed', async() => {
    Requester.get.mockReturnValue(Promise.resolve({clientName: 'ChatGPT'}));

    render(<AuthorizationConsent route={route} router={router} />);

    expect(await screen.findByText('sulu_admin.authorization_consent_error')).toBeInTheDocument();
});

test('Should render an error message when consent details cannot be loaded', async() => {
    Requester.get.mockReturnValue(Promise.reject(new Error('Not found')));

    render(<AuthorizationConsent route={route} router={router} />);

    expect(await screen.findByText('sulu_admin.authorization_consent_error')).toBeInTheDocument();
});

test('Should render without optional redirect URI and without scopes', async() => {
    Requester.get.mockReturnValue(Promise.resolve({clientName: 'ChatGPT', scopes: []}));

    render(<AuthorizationConsent route={route} router={router} />);

    expect(await screen.findByText('ChatGPT')).toBeInTheDocument();
    expect(screen.queryByText('sulu_admin.authorization_consent_redirect_uri')).not.toBeInTheDocument();
    expect(screen.queryByText('sulu_admin.authorization_consent_scopes')).not.toBeInTheDocument();
});
