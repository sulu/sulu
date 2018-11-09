// @flow

export type LinkTypeConfig = {
    resourceKey: string,
    adapter: string,
};

export type LinkTypeConfigs = {[provider: string]: LinkTypeConfig};
