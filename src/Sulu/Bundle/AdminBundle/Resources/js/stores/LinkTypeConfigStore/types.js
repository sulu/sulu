// @flow

export type LinkTypeConfig = {
    adapter: string,
    resourceKey: string,
};

export type LinkTypeConfigs = {[provider: string]: LinkTypeConfig};
