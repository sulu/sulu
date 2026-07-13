/* eslint-disable flowtype/require-valid-file-annotation */
import {mount} from 'enzyme';
import React from 'react';
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

function flushPromises() {
    return new Promise((resolve) => setTimeout(resolve, 0));
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

    const authorizationConsent = mount(<AuthorizationConsent route={route} router={router} />);

    expect(symfonyRouting.generate).toHaveBeenCalledWith(
        'sulu_mcp_server_oauth_consent_details',
        {requestId: 'request-1'}
    );
    expect(Requester.get).toHaveBeenCalledWith('/sulu_mcp_server_oauth_consent_details/request-1');

    await flushPromises();
    authorizationConsent.update();

    expect(authorizationConsent.text()).toContain('ChatGPT');
    expect(authorizationConsent.text()).toContain('Use MCP tools');
    expect(authorizationConsent.text()).toContain('https://chatgpt.com/oauth/callback');
});

test('Should approve authorization consent and redirect to continuation URL', async() => {
    Requester.get.mockReturnValue(Promise.resolve(consentDetails));
    Requester.post.mockReturnValue(Promise.resolve({redirectUrl: '/admin/mcp/authorize?sulu_mcp_consent=request-1'}));

    const authorizationConsent = mount(<AuthorizationConsent route={route} router={router} />);

    await flushPromises();
    authorizationConsent.update();

    authorizationConsent.find('button').at(1).simulate('click');

    expect(symfonyRouting.generate).toHaveBeenCalledWith(
        'sulu_mcp_server_oauth_consent_decision',
        {requestId: 'request-1'}
    );
    expect(Requester.post).toHaveBeenCalledWith('/sulu_mcp_server_oauth_consent_decision/request-1', {approved: true});

    await flushPromises();

    expect(window.location.assign).toHaveBeenCalledWith('/admin/mcp/authorize?sulu_mcp_consent=request-1');
});

test('Should deny authorization consent and redirect to continuation URL', async() => {
    Requester.get.mockReturnValue(Promise.resolve(consentDetails));
    Requester.post.mockReturnValue(Promise.resolve({redirectUrl: '/admin/mcp/authorize?sulu_mcp_consent=request-1'}));

    const authorizationConsent = mount(<AuthorizationConsent route={route} router={router} />);

    await flushPromises();
    authorizationConsent.update();

    authorizationConsent.find('button').at(0).simulate('click');

    expect(Requester.post).toHaveBeenCalledWith('/sulu_mcp_server_oauth_consent_decision/request-1', {approved: false});

    await flushPromises();

    expect(window.location.assign).toHaveBeenCalledWith('/admin/mcp/authorize?sulu_mcp_consent=request-1');
});

test('Should show an error and re-enable the buttons when the decision cannot be submitted', async() => {
    Requester.get.mockReturnValue(Promise.resolve(consentDetails));
    Requester.post.mockImplementation(() => Promise.reject(new Error('Server error')));

    const authorizationConsent = mount(<AuthorizationConsent route={route} router={router} />);

    await flushPromises();
    authorizationConsent.update();

    authorizationConsent.find('button').at(1).simulate('click');

    await flushPromises();
    authorizationConsent.update();

    expect(authorizationConsent.text()).toContain('sulu_admin.authorization_consent_decision_error');
    expect(window.location.assign).not.toHaveBeenCalled();
    expect(authorizationConsent.find('Button').at(0).prop('disabled')).toBe(false);
    expect(authorizationConsent.find('Button').at(1).prop('disabled')).toBe(false);
});

test('Should disable both buttons and show a loader while the decision is being submitted', async() => {
    Requester.get.mockReturnValue(Promise.resolve(consentDetails));
    Requester.post.mockReturnValue(new Promise(() => {}));

    const authorizationConsent = mount(<AuthorizationConsent route={route} router={router} />);

    await flushPromises();
    authorizationConsent.update();

    authorizationConsent.find('button').at(1).simulate('click');
    authorizationConsent.update();

    expect(Requester.post).toHaveBeenCalledTimes(1);
    expect(authorizationConsent.find('Button').at(0).prop('disabled')).toBe(true);
    expect(authorizationConsent.find('Button').at(1).prop('disabled')).toBe(true);
    expect(authorizationConsent.find('Button').at(1).prop('loading')).toBe(true);
});

test('Should render an error message when the consent details are malformed', async() => {
    Requester.get.mockReturnValue(Promise.resolve({clientName: 'ChatGPT'}));

    const authorizationConsent = mount(<AuthorizationConsent route={route} router={router} />);

    await flushPromises();
    authorizationConsent.update();

    expect(authorizationConsent.text()).toContain('sulu_admin.authorization_consent_error');
});

test('Should render an error message when consent details cannot be loaded', async() => {
    Requester.get.mockReturnValue(Promise.reject(new Error('Not found')));

    const authorizationConsent = mount(<AuthorizationConsent route={route} router={router} />);

    await flushPromises();
    authorizationConsent.update();

    expect(authorizationConsent.text()).toContain('sulu_admin.authorization_consent_error');
});

test('Should render without optional redirect URI and without scopes', async() => {
    Requester.get.mockReturnValue(Promise.resolve({clientName: 'ChatGPT', scopes: []}));

    const authorizationConsent = mount(<AuthorizationConsent route={route} router={router} />);

    await flushPromises();
    authorizationConsent.update();

    expect(authorizationConsent.text()).toContain('ChatGPT');
    expect(authorizationConsent.text()).not.toContain('sulu_admin.authorization_consent_redirect_uri');
    expect(authorizationConsent.text()).not.toContain('sulu_admin.authorization_consent_scopes');
});
