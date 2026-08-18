// @flow

/* conditions the platform refuses for that no retry resolves — the dialogs name them and point at an administrator */
export const ACCOUNT_LIMIT_MESSAGE_KEYS = {
    'sulu_ai.out_of_credits': 'outOfCredits',
    'sulu_ai.platform_unauthorized': 'platformUnauthorized',
    'sulu_ai.subscription_inactive': 'subscriptionInactive',
};

// the Requester rejects with the unread Response, which carries the messageKey the controller responded with
export function readMessageKey(error: Object): Promise<?string> {
    if (!error || typeof error.json !== 'function') {
        return Promise.resolve(undefined);
    }

    return error.json().then((data) => data?.messageKey).catch(() => undefined);
}

/* set once from the admin config, so places outside the AI dialogs — like toolbar actions — can offer it */
let contactEmail: ?string;

export function setAccountLimitContactEmail(email: ?string) {
    contactEmail = email;
}

export function getAccountLimitContactEmail(): ?string {
    return contactEmail;
}
