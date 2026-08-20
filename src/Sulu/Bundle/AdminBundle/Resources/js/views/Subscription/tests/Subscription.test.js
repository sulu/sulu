// @flow

import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom/extend-expect';
import symfonyRouting from 'fos-jsrouting/router';
import Requester from '../../../services/Requester';
import {translate} from '../../../utils/Translator';
import Subscription from '../Subscription';
import {setSubscriptionConfig} from '../subscriptionConfig';

jest.mock('fos-jsrouting/router');
jest.mock('../../../services/Requester', () => ({
    get: jest.fn(),
}));
jest.mock('../../../utils/Translator');
jest.mock('../../../containers/Toolbar', () => ({
    withToolbar: (Component) => Component,
}));

const payload = {
    credits: {
        hardLimit: 10000,
        included: 2000,
        remainingIncluded: 1240,
        remainingUntilHardLimit: 9240,
        remainingUntilSoftLimit: null,
        softLimit: null,
        used: 760,
    },
    nextSubscription: null,
    subscription: {
        bundle: 'starter',
        isActive: true,
        isTrial: false,
        payAsYouGo: false,
        scheduledCancellationAt: null,
        startedAt: '2026-03-01T00:00:00+00:00',
        type: 'base',
    },
};

beforeEach(() => {
    setSubscriptionConfig({
        contactEmail: 'admin@example.com',
        route: 'sulu_ai_platform.subscription',
    });

    symfonyRouting.generate.mockImplementation((route) => '/' + route);

    // $FlowFixMe
    translate.mockImplementation((key, parameters: ?Object) => {
        if (!parameters) {
            return key;
        }

        return key + ' ' + Object.values(parameters).join(' ');
    });
});

test('renders the subscription and credit data', async() => {
    Requester.get.mockReturnValue(Promise.resolve(payload));

    // $FlowFixMe
    render(<Subscription />);

    expect(await screen.findByText('sulu_ai_platform.subscription_type_base + Starter')).toBeInTheDocument();
    expect(screen.getByText('sulu_ai_platform.subscription_status_active')).toBeInTheDocument();
    expect(screen.getByText(/^1[\s,.]?240$/)).toBeInTheDocument();
    expect(screen.getByText(/^sulu_ai_platform\.subscription_credits_left 2[\s,.]?000$/)).toBeInTheDocument();
    expect(screen.getByText('sulu_ai_platform.subscription_credits_used 760')).toBeInTheDocument();
    expect(screen.getByText('sulu_ai_platform.subscription_credits_note_included_only')).toBeInTheDocument();
    const now = new Date();
    const firstOfNextMonth = new Date(now.getFullYear(), now.getMonth() + 1, 1)
        .toLocaleDateString(undefined, {day: '2-digit', month: '2-digit', year: 'numeric'});
    expect(
        screen.getByText('sulu_ai_platform.subscription_renews_on ' + firstOfNextMonth)
    ).toBeInTheDocument();
    expect(screen.getByText('sulu_ai_platform.subscription_contact_text')).toBeInTheDocument();
    expect(screen.getByText('admin@example.com')).toHaveAttribute('href', 'mailto:admin@example.com');
    expect(Requester.get).toHaveBeenCalledWith('/sulu_ai_platform.subscription');
});

test('renders the scheduled cancellation status', async() => {
    Requester.get.mockReturnValue(Promise.resolve({
        ...payload,
        subscription: {
            ...payload.subscription,
            scheduledCancellationAt: '2026-04-30T00:00:00+00:00',
        },
    }));

    // $FlowFixMe
    render(<Subscription />);

    expect(
        await screen.findByText(/sulu_ai_platform\.subscription_status_will_be_canceled/)
    ).toBeInTheDocument();
});

test('hides the contact box without a contact email', async() => {
    setSubscriptionConfig({
        contactEmail: undefined,
        route: 'sulu_ai_platform.subscription',
    });
    Requester.get.mockReturnValue(Promise.resolve(payload));

    // $FlowFixMe
    render(<Subscription />);

    expect(await screen.findByText('sulu_ai_platform.subscription_status_active')).toBeInTheDocument();
    expect(screen.queryByText('sulu_ai_platform.subscription_contact_text')).not.toBeInTheDocument();
});

test('renders the error state and retries on click', async() => {
    Requester.get.mockReturnValueOnce(Promise.reject(new Error('failed')));

    // $FlowFixMe
    render(<Subscription />);

    expect(await screen.findByText('sulu_ai_platform.subscription_unavailable')).toBeInTheDocument();

    Requester.get.mockReturnValueOnce(Promise.resolve(payload));
    await userEvent.click(screen.getByText('sulu_admin.try_again'));

    expect(await screen.findByText('sulu_ai_platform.subscription_status_active')).toBeInTheDocument();
});
