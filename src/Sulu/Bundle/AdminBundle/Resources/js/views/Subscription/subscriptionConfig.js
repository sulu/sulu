// @flow

export type SubscriptionConfig = {
    contactEmail: ?string,
    route: string,
};

let subscriptionConfig: ?SubscriptionConfig;

export function setSubscriptionConfig(config: ?SubscriptionConfig) {
    subscriptionConfig = config;
}

export function getSubscriptionConfig(): ?SubscriptionConfig {
    return subscriptionConfig;
}
